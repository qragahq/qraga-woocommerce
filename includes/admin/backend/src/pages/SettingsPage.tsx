import React, { useEffect } from 'react';
import { useQuery, useMutation, useQueryClient } from 'react-query';
import { useForm } from 'react-hook-form';
import { zodResolver } from '@hookform/resolvers/zod';
import * as z from 'zod';
import { toast } from "sonner";

// Import shadcn/ui components
import { Button } from "@/components/ui/button";
import {
  Card, CardContent, CardDescription, CardFooter, CardHeader, CardTitle,
} from "@/components/ui/card";
import {
  Form, FormControl, FormDescription, FormField, FormItem, FormLabel, FormMessage,
} from "@/components/ui/form";
import { Input } from "@/components/ui/input";
// import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "@/components/ui/select"; // If needed later

// Import from the new service file
import { fetchQragaSettings, saveQragaSettings, QragaSettings } from '@/services/settings.service';

// Define the validation schema using Zod (keys match QragaSettings for consistency)
const settingsFormSchema = z.object({
  siteId: z.string().min(1, { message: "Site ID cannot be empty." }).trim(),
  apiKey: z.string().min(1, { message: "API Key cannot be empty." }).trim(),
  endpointUrl: z.string().url({ message: "Please enter a valid URL." }).trim().or(z.literal('')), // Allow empty or valid URL
  widgetId: z.string().trim().optional(), // Optional
});

type SettingsFormSchemaType = z.infer<typeof settingsFormSchema>;

const SettingsPage: React.FC = () => {
  const queryClient = useQueryClient();

  const { data: currentSettings, isLoading, isError, error } = useQuery<QragaSettings, Error>(
    'qragaSettings',
    fetchQragaSettings
  );

  const form = useForm<SettingsFormSchemaType>({
    resolver: zodResolver(settingsFormSchema),
    defaultValues: {
      siteId: '',
      apiKey: '',
      endpointUrl: '',
      widgetId: '',
    },
  });

  useEffect(() => {
    if (currentSettings) {
      const formValues: SettingsFormSchemaType = {
        siteId: currentSettings.siteId || '',
        apiKey: currentSettings.apiKey || '',
        endpointUrl: currentSettings.endpointUrl || '',
        widgetId: currentSettings.widgetId || '',
      };
      form.reset(formValues);
    }
  }, [currentSettings, form]);

  const mutation = useMutation<QragaSettings, Error, SettingsFormSchemaType>(
    saveQragaSettings,
    {
      onSuccess: (savedData) => {
        queryClient.setQueryData('qragaSettings', savedData);
        toast.success("Settings Saved", {
          description: "Your Qraga settings have been updated successfully."
        });
        const formValues: SettingsFormSchemaType = {
            siteId: savedData.siteId || '',
            apiKey: savedData.apiKey || '',
            endpointUrl: savedData.endpointUrl || '',
            widgetId: savedData.widgetId || '',
        };
        form.reset(formValues);
      },
      onError: (err) => {
        toast.error("Error Saving Settings", {
          description: (err as any)?.message || "An unexpected error occurred."
        });
      },
    }
  );

  const onSubmit = (values: SettingsFormSchemaType) => {
    mutation.mutate(values);
  };

  if (isLoading) return <div className="p-4">Loading settings...</div>; // Simple loader
  if (isError) return <div className="p-4 text-destructive">Error loading settings: {error?.message}</div>;

  return (
    <div className="space-y-4">
      <h1 className="text-2xl font-semibold">Qraga Settings</h1>
      <Form {...form}>
        <form onSubmit={form.handleSubmit(onSubmit)} className="space-y-6">
          <Card>
            <CardHeader>
              <CardTitle>API Configuration</CardTitle>
              <CardDescription>Set up your connection to the Qraga API.</CardDescription>
            </CardHeader>
            <CardContent className="space-y-4">
              <FormField
                control={form.control}
                name="siteId"
                render={({ field }) => (
                  <FormItem>
                    <FormLabel>Site ID (previously Shop ID)</FormLabel>
                    <FormControl>
                      <Input placeholder="Enter your Qraga Site ID" {...field} />
                    </FormControl>
                    <FormDescription>Your unique identifier for this site in Qraga.</FormDescription>
                    <FormMessage />
                  </FormItem>
                )}
              />
              <FormField
                control={form.control}
                name="apiKey"
                render={({ field }) => (
                  <FormItem>
                    <FormLabel>API Key</FormLabel>
                    <FormControl>
                      <Input type="password" placeholder="Enter your Qraga API Key" {...field} />
                    </FormControl>
                    <FormDescription>Your secret API key for accessing Qraga.</FormDescription>
                    <FormMessage />
                  </FormItem>
                )}
              />
              <FormField
                control={form.control}
                name="endpointUrl"
                render={({ field }) => (
                  <FormItem>
                    <FormLabel>Endpoint URL</FormLabel>
                    <FormControl>
                      <Input type="url" placeholder="e.g., https://api.qraga.com/v1" {...field} />
                    </FormControl>
                    <FormDescription>The full base URL for the Qraga API.</FormDescription>
                    <FormMessage />
                  </FormItem>
                )}
              />
              <FormField
                control={form.control}
                name="widgetId"
                render={({ field }) => (
                  <FormItem>
                    <FormLabel>Widget ID</FormLabel>
                    <FormControl>
                      <Input placeholder="Enter your Qraga Widget ID (optional)" {...field} />
                    </FormControl>
                    <FormDescription>The ID for the Qraga product page widget, if used.</FormDescription>
                    <FormMessage />
                  </FormItem>
                )}
              />
            </CardContent>
            <CardFooter>
              <Button type="submit" disabled={mutation.isLoading || !form.formState.isDirty || !form.formState.isValid}>
                {mutation.isLoading ? (
                  <>
                    {/* Consider adding a Lucide icon like Loader2 here if you have lucide-react */}
                    <span className="animate-spin inline-block w-4 h-4 border-2 border-current border-t-transparent rounded-full mr-2" role="status" aria-label="loading"></span>
                    Saving...
                  </>
                ) : 'Save Settings'}
              </Button>
            </CardFooter>
          </Card>
        </form>
      </Form>
    </div>
  );
};

export default SettingsPage; 