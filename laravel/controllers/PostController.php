<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Post;
use App\Models\Comment;
use App\Models\Notification;
use App\Models\Reaction;
use App\Models\Report;
use App\Models\Tag;
use App\Http\Resources\PostResource;
use Carbon\Carbon;
// use App\Http\Resources\GetPostResource;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;

class PostController extends Controller
{
    public function myPosts(Request $request) {
        $posts = Post::with('user', 'user.personalInfo')->where('user_id', $request->user()->id)->orderBy('created_at', 'DESC')->get();
        return response()->json([
            'status' => 200,
            'posts'  => PostResource::collection($posts)
        ]);
    }

    public function userPosts($id) {
        $posts = Post::with('user', 'user.personalInfo')->where('user_id', $id)->orderBy('created_at', 'DESC')->get();
        return response()->json([
            'status' => 200,
            'posts'  => $posts
        ]);
    }

    public function publicPosts(Request $request) {
        $query = Post::with(['user', 'user.personalInfo'])
        ->where('status', 'published');
        
        $timeFilter = $request->query('time');
        // Log::info($request->query('title'));

        if ($timeFilter === 'today') {
            $query->whereDate('created_at', Carbon::today());
        } elseif ($timeFilter === 'week') {
            $query->whereBetween('created_at', [
                Carbon::now()->startOfWeek(),
                Carbon::now()->endOfWeek(),
            ]);
        } elseif ($timeFilter === 'month') {
            $query->whereMonth('created_at', Carbon::now()->month)
                ->whereYear('created_at', Carbon::now()->year);
        } elseif ($timeFilter === 'year') {
            $query->whereYear('created_at', Carbon::now()->year);
        }

        if($request->query('title') && $request->query('title') !== 'undefined') {
            $query->where('headline', 'Like', '%'.$request->query('title').'%');
        }

        if($request->query('category') && $request->query('category') !== 'undefined') {
            $query->where('category_id', $request->query('category'));
        }

        if($request->query('tag') && $request->query('tag') !== 'undefined' && $request->query('tag') !== 'null') {
            $query->where('tags','Like' ,'%'.$request->query('tag').'%');
        }

        $posts = $query->orderBy('created_at', 'DESC')->paginate(20);

        return response()->json([
            'status' => 200,
            'list' => PostResource::collection($posts),
            'next_page_url' => $posts->nextPageUrl(),
        ]);
    }

    public function publicPost($id) {
        try { 
            $post = Post::with('user', 'category', 'user.personalInfo', 'user.receiver', 'user.requestor', 'comments', 'comments.user')->where('id', $id)->first();
            return response()->json([
                'status' => 200,
                'post'  => new PostResource($post)
            ]);
        } catch(\Exception $e) {
            return response()->json([
                'status' => 500,
                'error'  => $e
            ]);
        }
    }

    public function postComments($id) {
        $comment = Comment::with('user', 'user.personalInfo')->where('post_id', $id)->orderBy('replies.comment', 'desc')->get();
        return response()->json([
            'status' => 200,
            'comment'  => $comment
        ]);
    }

    public function create(Request $request) {
        $validate = Validator::make($request->all(), [
            'headline'  => 'required|min:3',
            'content'   => 'required|min:10',
            'status'    => 'required',
        ]);

        if($validate->fails()) {
            return response()->json([
                'status' => 400,
                'errors' => $validate->errors()
            ]);
        }
        
        try {
            \DB::transaction(function() use ($request) {
                $file = null;
    
                if ($request->hasFile('file')) {
                    $file = $this->handleFileUpload($request->file('file'), 'uploads/post/file');
                }
    
                Post::create([
                    'headline'     => $request->headline,
                    'user_id'      => $request->user()->id,
                    'category_id'  => $request->category_id,
                    'tags'         => json_encode($request->tags),
                    'content'      => json_encode($request->content),
                    'status'       => $request->status,
                    // 'file_type'    => ($file && $file['extension']!='mp4') ? 'photo' : 'video',
                    'file_type'    => $file['extension'] ?? null,
                    'file'         => ($file && $file['file_path']!='') ? $file["file_path"] : null,
                    'thumbnail'    => ($file && $file['file_path']!='') ? $file["file_path"] : null,
                    'like'         => 0,
                    'dislike'      => 0,
                    'report'       => 0,
                    'comment'      => 0,
                    'views'        => 0
                ]);

                if($request->tags) {
                    $tags = explode(',', $request->tags);
                    $tags = array_map(function($tag) {
                        return strtolower(trim($tag));
                    }, $tags);
        
                    foreach($tags as $tag) {
                        $exists = Tag::where('tag_name','Like' ,'%'.$tag.'%')->first();
                        if(!$exists) {
                            Tag::create([
                                'tag_name' => $tag,
                                'post_number' => 1
                            ]);
                        } else {
                            $exists->increment('post_number', 1);
                        }
                    }
                }
            });
    
    
            return response()->json([
                'status'  => 200,
                'message' => 'Congratulations ! A Post has been created !'
            ]);
        } catch(\Exception $e) {
            return response()->json([
                'status'  => 500,
                'message' => $e
            ]);
        }
    }

    public function update(Request $request, $id) {
        $post = Post::find($id);
        $this->authorize('post', $post);
        $validate = Validator::make($request->all(), [
            'headline'  => 'required|min:3',
            'content'   => 'required|min:10',
            'status'    => 'required',
        ]);
        
        if($validate->fails()) {
            return response()->json([
                'status' => 400,
                'errors' => $validate->errors()
            ]);
        }
        
        try {
            \DB::transaction(function() use ($request, $id, $post) {    
                $file = null;
                $video = null;
                
                if($request->file == 'null' || $request->file == 'undefined') {
                    $file = null;
                } else if ($request->hasFile('file')) {
                    if ($post->file && \Storage::disk('public')->exists($post->file)) {
                        \Storage::disk('public')->delete($post->file);
                    }

                    $file = $this->handleFileUpload($request->file('file'), 'uploads/post/file');
                } else {
                    $file = $request->file;
                }

                Post::where('id', $id)->where('user_id', auth()->id())->update([
                    'headline'     => $request->headline,
                    'user_id'      => $request->user()->id,
                    'category_id'  => $request->category_id,
                    'tags'         => json_encode($request->tags),
                    'content'      => json_encode($request->content),
                    'status'       => $request->status,
                    'file_type'    => ($file && $file['extension']=='mp4') ? 'video' : 'photo',
                    'file'         => ($file && $file['file_path']!='') ? $file["file_path"] : null,
                    'thumbnail'    => ($file && $file['file_path']!='') ? $file["file_path"] : null
                ]);

                if($request->tags) {
                    $tags = explode(',', $request->tags);
                    $tags = array_map(function($tag) {
                        return strtolower(trim($tag));
                    }, $tags);
    
                    foreach($tags as $tag) {
                        $exists = Tag::where('tag_name','Like' ,'%'.$tag.'%')->first();
                        if(!$exists) {
                            Tag::create([
                                'tag_name' => $tag,
                                'post_number' => 1
                            ]);
                        } else {
                            $exists->increment('post_number', 1);
                        }
                    }
                }
            });
    
            return response()->json([
                'status'  => 200,
                'message' => 'Congratulations ! The Post has been updated !'
            ]);
        } catch(\Exception $e) {
            return response()->json([
                'status'  => 500,
                'message' => $e
            ]);
        }
    }

    public function getById($id) {
        try {
            $post = Post::where('id', $id)->where('user_id', auth()->id())->first();
            return response()->json([
                'status'  => 200,
                'post' => new PostResource($post)
            ]);
        } catch(\Exception $e) {
            return response()->json([
                'status'  => 500,
                'message' => $e
            ]);
        }
    }

    public function popular()
    {
        try {
            $posts = Post::with('user', 'user.personalInfo')
            ->where('status', 'published')
            ->orderBy('views', 'DESC')
            ->orderBy('like', 'DESC')
            ->orderBy('comment', 'DESC')
            ->get();
            return response()->json([
                'status' => 200,
                'list'  => $posts
            ]);
        } catch(\Exception $e) {
            return response()->json([
                'status'  => 500,
                'message' => $e
            ]);
        }
    }

    public function delete($id)
    {
        $post = Post::find($id);
        $this->authorize('post', $post);
        if(!$post) {
            return response()->json([
                'status'   => 404,
                'message'  => 'No Data Found !'
            ]);    
        }

        \DB::transaction(function() use($post) {
            if ($post->file && \Storage::disk('public')->exists($post->file)) {
                \Storage::disk('public')->delete($post->file);
            }

            $commentIds = Comment::where('post_id', $post->id)->pluck('id');
            $notificationIds = Notification::where('post_id', $post->id)->pluck('id');
            $reportIds = Report::where('reported_to', $post->id)->where('type', 'post')->pluck('id');
            $reactionIds = Reaction::where('post_id', $post->id)->pluck('id');
    
            $post->delete();
            Comment::whereIn('id', $commentIds)->delete();
            Notification::whereIn('id', $commentIds)->delete();
            Report::whereIn('id', $commentIds)->delete();
            Reaction::whereIn('id', $commentIds)->delete();
        });

        return response()->json([
            'status'   => 200
        ]);
    }

    public function trendings() 
    {
        try {
            $trendings = Tag::orderBy('post_number', 'DESC')->take(5)->get();

            return response()->json([
                'status' => 200,
                'list'  => $trendings
            ]);
        } catch(\Exception $e) {
            return response()->json([
               'status'  => 500,
               'message' => $e
            ]);
        }
    }

    private function handleFileUpload($file, $path) {
        $extension = $file->getClientOriginalExtension();
        $filename = time() . '.' . $extension;
        $file->storeAs($path, $filename, 'public');
        return [
            'file_path' => $path . '/' . $filename,
            'extension' => ($extension == 'mp4') ? 'video' : 'photo'
        ];
    }
}
