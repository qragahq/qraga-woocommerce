import React, { useState, useEffect, useRef } from 'react';
import { useMutation } from 'react-query';
import apiFetch from '@wordpress/api-fetch';
import { toast } from 'sonner';

// Import shadcn/ui components
import { Button } from "@/components/ui/button";
import {
  Card, CardContent, CardDescription, CardFooter, CardHeader, CardTitle,
} from "@/components/ui/card";
// import { Progress } from "@/components/ui/progress"; // Assuming you have a Progress component - Temporarily removed

// Interface for the initial response from the /export/ endpoint
interface BulkExportTriggerResponse {
    status: 'queued' | 'complete' | 'no_products' | 'error' | 'active_job_found'; // Added 'active_job_found'
    message: string;
    job_id?: string;
    total_products?: number; // For new jobs
    job_details?: BulkExportStatusResponse; // For existing jobs
}

// Interface for the response from the /export/status/{job_id} endpoint
interface BulkExportStatusResponse {
    status: 'queued' | 'processing' | 'completed' | 'failed' | 'error_scheduling_next'; // Job status
    total: number;
    processed: number;
    batches: number;
    start_time: number;
    end_time?: number;
    errors: Array<{ timestamp: number; product_id?: number; batch?: number; message?: string; messages?: string[] }>;
    error_ids: number[];
    job_id: string;
    // Potentially other fields like percentage if backend adds it
}

// Function to call the bulk export trigger API endpoint
const triggerBulkExport = async (): Promise<BulkExportTriggerResponse> => {
    return await apiFetch<BulkExportTriggerResponse>({ path: '/qraga/v1/export/', method: 'POST' });
};

// Function to get the bulk export status by specific Job ID
const getBulkExportStatus = async (jobId: string): Promise<BulkExportStatusResponse> => {
    return await apiFetch<BulkExportStatusResponse>({ path: `/qraga/v1/export/status/${jobId}`, method: 'GET' });
};

// Function to get the current active job status on page load
const getCurrentActiveJob = async (): Promise<BulkExportStatusResponse | { status: 'no_active_job', message: string }> => {
    return await apiFetch<BulkExportStatusResponse | { status: 'no_active_job', message: string }>({
        path: '/qraga/v1/export/current-job',
        method: 'GET'
    });
};

const SyncPage: React.FC = () => {
    const [jobId, setJobId] = useState<string | null>(null);
    const [totalProducts, setTotalProducts] = useState<number | null>(null);
    const [processedCount, setProcessedCount] = useState<number>(0);
    const [isSyncing, setIsSyncing] = useState<boolean>(false);
    const [currentSyncStatusMessage, setCurrentSyncStatusMessage] = useState<string | null>('Checking for active jobs...'); // Initial message
    const [jobDetails, setJobDetails] = useState<BulkExportStatusResponse | null>(null);
    
    const pollingIntervalIdRef = useRef<number | null>(null);

    // Effect to fetch current job status on component mount
    useEffect(() => {
        const fetchCurrentJob = async () => {
            try {
                const currentJobData = await getCurrentActiveJob();

                // Check if the response indicates an active job (i.e., it's not the 'no_active_job' status object)
                if (currentJobData.status !== 'no_active_job') {
                    const activeJob = currentJobData as BulkExportStatusResponse; // Type assertion, safe after the check

                    if (!activeJob.job_id) {
                        console.warn('Active job data received on load, but job_id is missing in the response object.', activeJob);
                        setCurrentSyncStatusMessage('No active job found (missing ID in data).');
                        setIsSyncing(false);
                        return;
                    }

                    setJobId(activeJob.job_id);
                    setJobDetails(activeJob);
                    setTotalProducts(activeJob.total);
                    setProcessedCount(activeJob.processed);
                    setCurrentSyncStatusMessage(`Discovered active job: ${activeJob.job_id} - Status: ${activeJob.status}`);
                    setIsSyncing(true);
                    toast.info(`Discovered an ongoing sync job (${activeJob.job_id}). Resuming monitoring.`);

                    const activePollingStatuses = ['queued', 'processing', 'error_scheduling_next'];
                    if (activePollingStatuses.includes(activeJob.status)) {
                        startPolling(activeJob.job_id);
                    } else {
                        setIsSyncing(false); // Job is found but already completed/failed
                    }
                } else {
                    // No active job, the response was { status: 'no_active_job' }
                    setCurrentSyncStatusMessage('No active sync job found. Click button to start.');
                    setIsSyncing(false);
                }
            } catch (error: any) {
                console.error('Failed to fetch current active job status:', error);
                setCurrentSyncStatusMessage('Error checking for active jobs. Please try starting a new sync.');
                setIsSyncing(false);
                toast.error(`Failed to check for active jobs: ${error.message}`);
            }
        };

        fetchCurrentJob();

        // Cleanup polling on component unmount
        return () => {
            stopPolling();
        };
    }, []); // Empty dependency array means this runs once on mount

    const exportMutation = useMutation<BulkExportTriggerResponse, Error>(triggerBulkExport, {
        onSuccess: (data) => {
            if (data.status === 'active_job_found' && data.job_id && data.job_details) {
                // Found an existing active job
                setJobId(data.job_id);
                setJobDetails(data.job_details);
                setTotalProducts(data.job_details.total);
                setProcessedCount(data.job_details.processed);
                setCurrentSyncStatusMessage(data.message || `Resuming monitor for job: ${data.job_id} - Status: ${data.job_details.status}`);
                setIsSyncing(true);
                toast.info(data.message || `Resuming monitoring for active job: ${data.job_id}`);

                const activeStatusesForPolling = ['queued', 'processing', 'error_scheduling_next'];
                if (activeStatusesForPolling.includes(data.job_details.status)) {
                    startPolling(data.job_id);
                } else {
                    // Job is found but already completed/failed, update UI but don't poll
                    setIsSyncing(false); 
                }
            } else if (data.job_id && typeof data.total_products !== 'undefined' && data.status === 'queued') {
                // Successfully queued a new job
                setCurrentSyncStatusMessage(data.message);
                setJobId(data.job_id);
                setTotalProducts(data.total_products);
                setProcessedCount(0); 
                setJobDetails(null); 
                setIsSyncing(true);
                toast.info(`Sync job ${data.job_id} queued for ${data.total_products} products.`);
                startPolling(data.job_id);
            } else if (data.status === 'complete' || data.status === 'no_products') {
                // New job, but it completed immediately (e.g., no products)
                setCurrentSyncStatusMessage(data.message);
                setIsSyncing(false);
                setTotalProducts(data.total_products || 0);
                setProcessedCount(data.total_products || 0); 
                toast.success(data.message || 'No products to sync or process already complete.');
            } else {
                // Other initial statuses or errors from trigger (e.g., status 'error')
                setCurrentSyncStatusMessage(data.message || 'Failed to start or find sync job.');
                setIsSyncing(false);
                toast.error(data.message || 'Failed to start sync job.');
            }
        },
        onError: (error) => {
            setIsSyncing(false);
            setCurrentSyncStatusMessage(null);
            toast.error(`Failed to trigger bulk sync: ${error.message}`);
            console.error('Bulk sync trigger error:', error);
        },
    });

    const pollJobStatus = async (currentJobId: string) => {
        if (!currentJobId) return;

        try {
            const statusData = await getBulkExportStatus(currentJobId);
            setJobDetails(statusData);
            setProcessedCount(statusData.processed);
            setTotalProducts(statusData.total); 

            let statusMsg = `Processing... ${statusData.processed} of ${statusData.total} products. Batches: ${statusData.batches}.`;
            if (statusData.status === 'queued') statusMsg = 'Job is queued, waiting to start...';
            else if (statusData.status === 'completed') statusMsg = `Job completed. Processed: ${statusData.processed}/${statusData.total}.`;
            else if (statusData.status === 'failed') statusMsg = `Job failed. Processed: ${statusData.processed}/${statusData.total}.`;
            else if (statusData.status === 'error_scheduling_next') statusMsg = `Job error (scheduling next). Processed: ${statusData.processed}/${statusData.total}.`;
            
            setCurrentSyncStatusMessage(statusMsg);

            if (statusData.status === 'completed' || statusData.status === 'failed' || statusData.status === 'error_scheduling_next') {
                stopPolling();
                setIsSyncing(false);
                if (statusData.status === 'completed') {
                    if (statusData.errors && statusData.errors.length > 0) {
                        toast.warning(`Sync completed with ${statusData.errors.length} error log(s). Check details.`);
                    } else {
                        toast.success('Bulk synchronization completed successfully!');
                    }
                } else {
                    toast.error(`Synchronization ended with status: ${statusData.status}. Check details.`);
                }
                // setCurrentSyncStatusMessage is already set above with final status
            }
        } catch (error: any) {
            stopPolling();
            setIsSyncing(false);
            toast.error(`Error fetching sync status: ${error.message}`);
            setCurrentSyncStatusMessage('Error fetching sync status. Please check console.');
            console.error('Polling error:', error);
        }
    };

    const startPolling = (currentJobId: string) => {
        stopPolling(); 
        pollJobStatus(currentJobId); 
        pollingIntervalIdRef.current = setInterval(() => pollJobStatus(currentJobId), 5000); 
    };

    const stopPolling = () => {
        if (pollingIntervalIdRef.current) {
            clearInterval(pollingIntervalIdRef.current);
            pollingIntervalIdRef.current = null;
        }
    };

    const handleSyncClick = () => {
        toast.info('Initiating bulk synchronization...');
        // Clear only some visual states, jobID might be refetched if an active one is found
        setProcessedCount(0);
        setCurrentSyncStatusMessage('Initiating...');
        // setJobId(null); // Don't nullify immediately if we might pick up an existing job
        // setTotalProducts(null);
        // setJobDetails(null);
        setIsSyncing(true); 
        exportMutation.mutate();
    };

    const progressPercentage = totalProducts && totalProducts > 0 ? (processedCount / totalProducts) * 100 : 0;

    return (
        <div className="space-y-6 p-4 md:p-6">
            <h1 className="text-2xl font-semibold">Bulk Product Synchronization</h1>
            <Card>
                <CardHeader>
                    <CardTitle>Trigger Bulk Sync to Qraga</CardTitle>
                    <CardDescription>
                        Click the button to synchronize all publishable WooCommerce products with Qraga.
                        This process runs in the background. You can monitor its progress below.
                        Ensure API settings are correct on the Settings page.
                    </CardDescription>
                </CardHeader>
                <CardContent>
                    <Button
                        onClick={handleSyncClick}
                        disabled={isSyncing || exportMutation.isLoading}
                        size="lg"
                        className="w-full sm:w-auto"
                    >
                        {isSyncing || exportMutation.isLoading ? (
                            <>
                                <span className="animate-spin inline-block w-5 h-5 border-2 border-current border-t-transparent rounded-full mr-3" role="status" aria-label="loading"></span>
                                {jobId ? 'Syncing in Progress...' : (exportMutation.isLoading ? 'Initiating Sync...' : 'Checking status...')}
                            </>
                        ) : 'Start Bulk Sync'}
                    </Button>
                </CardContent>
            </Card>

            {(isSyncing || jobDetails) && (
                <Card>
                    <CardHeader>
                        <CardTitle>Synchronization Status</CardTitle>
                        {jobId && <CardDescription>Job ID: {jobId}</CardDescription>}
                    </CardHeader>
                    <CardContent className="space-y-4">
                        {currentSyncStatusMessage && <p className="text-sm text-muted-foreground">{currentSyncStatusMessage}</p>}
                        
                        {(totalProducts !== null && totalProducts > 0) && (
                            <div>
                                {/* <Progress value={progressPercentage} className="w-full" /> */}
                                <div style={{ width: '100%', backgroundColor: '#e0e0e0', borderRadius: '4px', overflow: 'hidden' }}>
                                    <div style={{ width: `${progressPercentage}%`, backgroundColor: '#4caf50', height: '20px', textAlign: 'center', color: 'white', lineHeight: '20px' }}>
                                        {progressPercentage.toFixed(0)}%
                                    </div>
                                </div>
                                <p className="text-sm text-muted-foreground mt-2">
                                    Processed {processedCount} of {totalProducts} products ({progressPercentage.toFixed(2)}%)
                                </p>
                            </div>
                        )}

                        {jobDetails && (jobDetails.errors.length > 0 || jobDetails.error_ids.length > 0) && (
                            <div className="space-y-2 pt-2 border-t mt-4">
                                <h4 className="font-medium text-destructive">Errors Encountered:</h4>
                                {jobDetails.error_ids.length > 0 && (
                                    <div>
                                        <p className="text-sm font-medium text-destructive">Product IDs with errors ({jobDetails.error_ids.length}):</p>
                                        <pre className="mt-1 text-xs p-2 bg-destructive/10 text-destructive rounded-md max-h-32 overflow-y-auto">
                                            {jobDetails.error_ids.join(', ')}
                                        </pre>
                                    </div>
                                )}
                                {jobDetails.errors.length > 0 && (
                                    <div>
                                        <p className="text-sm font-medium text-destructive">Detailed Error Logs:</p>
                                        <ul className="mt-1 list-disc list-inside text-xs p-2 bg-destructive/10 text-destructive-foreground rounded-md max-h-60 overflow-y-auto space-y-1">
                                            {jobDetails.errors.map((err, index) => (
                                                <li key={index}>
                                                    {err.timestamp && <span className="text-muted-foreground text-xs">[{new Date(err.timestamp * 1000).toLocaleString()}] </span>}
                                                    {err.batch && <span className="font-semibold">Batch {err.batch}: </span>}
                                                    {err.product_id && <span className="font-semibold">Product ID {err.product_id}: </span>}
                                                    <span className="whitespace-pre-wrap">{err.message || (err.messages && err.messages.join(', '))}</span>
                                                </li>
                                            ))}
                                        </ul>
                                    </div>
                                )}
                            </div>
                        )}
                    </CardContent>
                    {jobDetails && (
                         <CardFooter className="border-t pt-4">
                            <details className="w-full">
                                <summary className="text-sm font-medium cursor-pointer">Show Raw Job Details</summary>
                                <pre className="mt-2 text-xs p-2 bg-muted rounded-md max-h-60 overflow-y-auto">
                                    {JSON.stringify(jobDetails, null, 2)}
                                </pre>
                            </details>
                         </CardFooter>
                    )}
                </Card>
            )}
        </div>
    );
};

export default SyncPage; 