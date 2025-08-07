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
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "@/components/ui/select";

// Import from the new service file
import { fetchQragaSettings, saveQragaSettings, QragaSettings } from '@/services/settings.service';

// Define the validation schema using Zod (keys match SettingsFormData for consistency)
const settingsFormSchema = z.object({
  siteId: z.string().min(1, { message: "Site ID cannot be empty." }).trim(),
  apiKey: z.string().min(1, { message: "API Key cannot be empty." }).trim(),
  region: z.string().min(1, { message: "Region cannot be empty." }).trim(),
  apiVersion: z.string().min(1, { message: "API version cannot be empty." }).trim(),
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
      region: 'US',
      apiVersion: 'v1',
      widgetId: '',
    },
  });

  useEffect(() => {
    if (currentSettings) {
      const formValues: SettingsFormSchemaType = {
        siteId: currentSettings.siteId || '',
        apiKey: currentSettings.apiKey || '',
        region: currentSettings.region || 'US',
        apiVersion: currentSettings.apiVersion || 'v1',
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
            region: savedData.region || 'US',
            apiVersion: savedData.apiVersion || 'v1',
            widgetId: savedData.widgetId || '',
        };
        form.reset(formValues);
      },
      onError: (err) => {
        toast.error("Error Saving Settings", {
          description: (err as Error)?.message || "An unexpected error occurred."
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
                    <FormLabel>Site ID</FormLabel>
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
                name="widgetId"
                render={({ field }) => (
                  <FormItem>
                    <FormLabel>Widget ID</FormLabel>
                    <FormControl>
                      <Input placeholder="Enter your Qraga Widget ID (optional)" {...field} />
                    </FormControl>
                    <FormDescription>The ID of the Qraga shopping assistant for product pages.</FormDescription>
                    <FormMessage />
                  </FormItem>
                )}
              />
              <FormField
                control={form.control}
                name="region"
                render={({ field }) => (
                  <FormItem>
                    <FormLabel>Region</FormLabel>
                    <Select onValueChange={field.onChange} defaultValue={field.value} disabled>
                      <FormControl>
                        <SelectTrigger>
                          <SelectValue placeholder="Select region" />
                        </SelectTrigger>
                      </FormControl>
                      <SelectContent>
                        <SelectItem value="US">United States</SelectItem>
                      </SelectContent>
                    </Select>
                    <FormDescription>Your Qraga service region (currently only US available).</FormDescription>
                    <FormMessage />
                  </FormItem>
                )}
              />
              <FormField
                control={form.control}
                name="apiVersion"
                render={({ field }) => (
                  <FormItem>
                    <FormLabel>API Version</FormLabel>
                    <Select onValueChange={field.onChange} defaultValue={field.value} disabled>
                      <FormControl>
                        <SelectTrigger>
                          <SelectValue placeholder="Select API version" />
                        </SelectTrigger>
                      </FormControl>
                      <SelectContent>
                        <SelectItem value="v1">v1</SelectItem>
                      </SelectContent>
                    </Select>
                    <FormDescription>Qraga API version (currently only v1 available).</FormDescription>
                    <FormMessage />
                  </FormItem>
                )}
              />
            </CardContent>
            <CardFooter>
              <Button type="submit" disabled={mutation.isLoading}>
                {mutation.isLoading ? (
                  <>
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