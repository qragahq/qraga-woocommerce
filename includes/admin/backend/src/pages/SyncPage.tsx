import React, { useState } from 'react';
import { useMutation } from 'react-query';
import apiFetch from '@wordpress/api-fetch';
import { toast } from 'sonner';

// Import shadcn/ui components
import { Button } from "@/components/ui/button";
import {
  Card, CardContent, CardDescription, CardFooter, CardHeader, CardTitle,
} from "@/components/ui/card";

// Interface for the expected response from the bulk sync endpoint
interface BulkSyncResponse {
    message: string;
    success?: boolean;
    processed?: number;
    batches?: number;
    synced_products?: number; // Kept for compatibility if API varies
    errors?: any; 
    error_ids?: number[];
}

// Function to call the bulk sync API endpoint
const triggerBulkSync = async (): Promise<BulkSyncResponse> => {
    const response = await apiFetch<BulkSyncResponse>({ path: '/qraga/v1/export/', method: 'POST' });
    return response;
};

const SyncPage: React.FC = () => {
    const [syncResult, setSyncResult] = useState<BulkSyncResponse | null>(null);

    const mutation = useMutation<BulkSyncResponse, Error>(triggerBulkSync, {
        onSuccess: (data) => {
            setSyncResult(data);
            if (data.success === false || (typeof data.errors !== 'undefined' && ((Array.isArray(data.errors) && data.errors.length > 0) || (typeof data.errors === 'number' && data.errors > 0)))) {
                let errorCount = 0;
                if (Array.isArray(data.errors)) errorCount = data.errors.length;
                else if (typeof data.errors === 'number') errorCount = data.errors;
                else if (data.error_ids) errorCount = data.error_ids.length;

                toast.error(`Bulk sync completed with ${errorCount} error(s). Processed: ${data.processed || data.synced_products || 0}.`);
                
                if (Array.isArray(data.errors) && data.errors.length > 0) {
                    console.error('Bulk sync errors:', data.errors);
                    toast.info('Check browser console for error details.');
                } else if (data.error_ids && data.error_ids.length > 0) {
                    console.error('Products that failed to sync:', data.error_ids);
                    toast.info('Check browser console for IDs of products that failed.');
                }
            } else if ((data.processed && data.processed > 0) || (data.synced_products && data.synced_products > 0)) {
                toast.success(`Bulk sync successful! ${data.processed || data.synced_products} product(s) processed.`);
                if(data.batches) toast.info(`${data.batches} batch(es) processed.`);
            } else {
                toast.info(data.message || 'Bulk sync request processed, but no products were updated or an issue occurred.');
            }
        },
        onError: (error) => {
            setSyncResult(null);
            toast.error(`Bulk sync failed: ${error.message}`);
            console.error('Bulk sync error:', error);
        },
    });

    const handleSyncClick = () => {
        toast.info('Starting bulk synchronization...');
        setSyncResult(null);
        mutation.mutate();
    };

    return (
        <div className="space-y-4">
            <h1 className="text-2xl font-semibold">Bulk Product Synchronization</h1>
            <Card>
                <CardHeader>
                    <CardTitle>Trigger Bulk Sync to Qraga</CardTitle>
                    <CardDescription>
                        Click the button below to synchronize all your WooCommerce products with Qraga.
                        This may take some time depending on the number of products in your store.
                        Ensure your API settings are correctly configured on the Settings page.
                    </CardDescription>
                </CardHeader>
                <CardContent>
                    <Button
                        onClick={handleSyncClick}
                        disabled={mutation.isLoading}
                        size="lg"
                    >
                        {mutation.isLoading ? (
                            <>
                                <span className="animate-spin inline-block w-5 h-5 border-2 border-current border-t-transparent rounded-full mr-3" role="status" aria-label="loading"></span>
                                Syncing...
                            </>
                        ) : 'Start Bulk Sync'}
                    </Button>
                </CardContent>

                {mutation.isLoading && (
                    <CardFooter className='pt-4'>
                        <div className="flex items-center w-full text-muted-foreground">
                            <span className="animate-spin inline-block w-4 h-4 border-2 border-current border-t-transparent rounded-full mr-2" role="status" aria-label="loading"></span>
                            <span>Processing products, please wait... This may take several minutes.</span>
                        </div>
                    </CardFooter>
                )}

                {syncResult && !mutation.isLoading && (
                    <CardFooter className='flex-col items-start gap-y-2 pt-4 border-t'>
                        <h3 className="font-semibold">Sync Results:</h3>
                        <p className={syncResult.success === false || (syncResult.errors && (Array.isArray(syncResult.errors) ? syncResult.errors.length > 0 : syncResult.errors > 0)) ? 'text-destructive' : 'text-green-600'}>
                            <strong>{syncResult.message}</strong>
                        </p>
                        {typeof syncResult.processed !== 'undefined' && (
                            <p>Products processed: {syncResult.processed}</p>
                        )}
                        {typeof syncResult.synced_products !== 'undefined' && typeof syncResult.processed === 'undefined' && (
                            <p>Products synced: {syncResult.synced_products}</p>
                        )}
                        {typeof syncResult.batches !== 'undefined' && (
                            <p>Batches processed: {syncResult.batches}</p>
                        )}
                        {(typeof syncResult.errors !== 'undefined' && ((Array.isArray(syncResult.errors) && syncResult.errors.length > 0) || (typeof syncResult.errors === 'number' && syncResult.errors > 0))) && (
                            <p className="text-destructive">Errors encountered: {Array.isArray(syncResult.errors) ? syncResult.errors.length : syncResult.errors}</p>
                        )}
                        {syncResult.error_ids && syncResult.error_ids.length > 0 && (
                            <div className="w-full">
                                <p className="text-destructive font-medium">Product IDs with errors:</p>
                                <pre className="mt-1 text-xs p-2 bg-muted rounded-md max-h-32 overflow-y-auto">
                                    {syncResult.error_ids.join(', ')}
                                </pre>
                            </div>
                        )}
                        {Array.isArray(syncResult.errors) && syncResult.errors.length > 0 && (
                            <div className="w-full">
                                <p className="text-destructive font-medium">Error Messages:</p>
                                <ul className="mt-1 list-disc list-inside text-xs p-2 bg-muted rounded-md max-h-40 overflow-y-auto">
                                    {syncResult.errors.map((err, index) => (
                                        <li key={index}><pre className="inline whitespace-pre-wrap">{typeof err === 'object' ? JSON.stringify(err) : err}</pre></li>
                                    ))}
                                </ul>
                            </div>
                        )}
                    </CardFooter>
                )}
            </Card>
        </div>
    );
};

export default SyncPage; 