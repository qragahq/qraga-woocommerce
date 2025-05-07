import React, { useState } from 'react';
import { useQuery } from 'react-query';
import { getPostsService, type PostsResponse } from '../services/posts.service';
import { Button } from '@/components/ui/button'; // For pagination later
// import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table'; // For table display later

const PostsPage: React.FC = () => {
  const [currentPage, setCurrentPage] = useState(1);

  const {
    data: postsData,
    isLoading,
    isError,
    error,
  } = useQuery<PostsResponse, Error>(
    ['posts', currentPage], // Query key: includes page number so it refetches when page changes
    () => getPostsService(currentPage),
    {
      keepPreviousData: true, // Good for pagination UX
    }
  );

  if (isLoading) {
    return (
      <div>
        <h1 className="text-2xl font-semibold mb-4">Posts</h1>
        <p>Loading posts...</p>
      </div>
    );
  }

  if (isError) {
    return (
      <div>
        <h1 className="text-2xl font-semibold mb-4">Posts</h1>
        <p className="text-destructive">Error fetching posts: {error?.message}</p>
      </div>
    );
  }

  return (
    <div>
      <h1 className="text-2xl font-semibold mb-4">WordPress Posts</h1>
      
      {postsData && postsData.data.length > 0 ? (
        <div className="border rounded-md">
          {/* Using simple divs for now, can upgrade to shadcn/ui Table later */}
          <div className="grid grid-cols-[1fr_200px_150px_100px] p-2 font-medium border-b bg-muted/50">
            <div>Title</div>
            <div>Author</div>
            <div>Date</div>
            <div>Status</div>
          </div>
          {postsData.data.map((post) => (
            <div key={post.postID} className="grid grid-cols-[1fr_200px_150px_100px] p-2 border-b last:border-b-0 hover:bg-muted/20">
              <div>{post.postName}</div>
              <div>{post.postAuthor}</div>
              <div>{post.postDate}</div>
              <div>{post.postStatus}</div>
            </div>
          ))}
        </div>
      ) : (
        <p>No posts found.</p>
      )}

      {postsData && postsData.numOfPages > 1 && (
        <div className="flex items-center justify-end space-x-2 py-4">
          <Button
            variant="outline"
            size="sm"
            onClick={() => setCurrentPage((prev) => Math.max(prev - 1, 1))}
            disabled={currentPage === 1}
          >
            Previous
          </Button>
          <span className="text-sm">
            Page {currentPage} of {postsData.numOfPages}
          </span>
          <Button
            variant="outline"
            size="sm"
            onClick={() => setCurrentPage((prev) => Math.min(prev + 1, postsData.numOfPages))}
            disabled={currentPage === postsData.numOfPages}
          >
            Next
          </Button>
        </div>
      )}
    </div>
  );
};

export default PostsPage; 