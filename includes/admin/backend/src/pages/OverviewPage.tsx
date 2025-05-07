import React from 'react';
import {
  Card, CardContent, CardDescription, CardHeader, CardTitle,
} from "@/components/ui/card";

const OverviewPage: React.FC = () => {
  return (
    <div className="space-y-4">
      <h1 className="text-2xl font-semibold">Qraga Plugin Overview</h1>
      <Card>
        <CardHeader>
          <CardTitle>Welcome to Qraga!</CardTitle>
          <CardDescription>
            This is your main overview page for the Qraga WooCommerce integration.
          </CardDescription>
        </CardHeader>
        <CardContent>
          <p>
            Navigate using the sidebar to configure settings or perform bulk product synchronization.
          </p>
          {/* You can add more summary information or quick links here later */}
        </CardContent>
      </Card>
    </div>
  );
};

export default OverviewPage; 