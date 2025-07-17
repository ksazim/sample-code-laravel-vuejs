<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Reaction;
use App\Models\Comment;
use App\Models\Post;
use App\Models\Message;
use App\Models\Connection;
use App\Models\CaseNotification;
use App\Models\Notification;
use App\Events\NewNotification;
use App\Http\Resources\ReactionUserResource;
use Illuminate\Support\Facades\Validator;
use DB;

class ReactionController extends Controller
{
    public function likePost(Request $request) {
        try {
            $post = Post::find($request->post_id);

            $exists = Reaction::where('post_id', $request->post_id)->where('type', 'like')->where('user_id', $request->user()->id)->first();
            
            if($exists) {
                    $post->decrement('like', 1);
                    $exists->delete();

                    $notification = Notification::create([
                        'who' => $request->user()->id,
                        'whom' => $post->user_id,
                        'type' => 'post',
                        'type_id' => $request->post_id,
                        'post_id' => $request->post_id,
                        'activity' => 'Unliked your Post',
                    ]);
            } else {
                $post->increment('like', 1);

                Reaction::create([
                    'user_id'   => $request->user()->id,
                    'type'      => $request->type,
                    'post_id'   => $request->post_id,
                ]);

                $notification = Notification::create([
                    'who' => $request->user()->id,
                    'whom' => $post->user_id,
                    'type' => 'post',
                    'type_id' => $request->post_id,
                    'post_id' => $request->post_id,
                    'activity' => 'Liked your Post',
                ]);

                // $this->sendNotificationToUser($notification, $post->user_id);
            }
    
            return response()->json([
                'status'  => 200
            ]);
        } catch(\Exception $e) {
            return response()->json([
                'status'  => 500,
                'message' => $e
            ]);
        }
    }

    public function likeComment(Request $request) {
        try {
            $post = Post::find($request->post_id);
            $exists = Reaction::where('comment_id', $request->comment_id)->where('type', 'like')->where('user_id', $request->user()->id)->first();
            
            if($exists) {
                Comment::where('id', $request->comment_id)->decrement('like', 1);
                $exists->delete();

                Notification::create([
                    'who' => $request->user()->id,
                    'whom' => $post->whom,
                    'type' => 'comment',
                    'type_id' => $request->comment_id,
                    'post_id' => $request->post_id,
                    'comment_id' => $request->comment_id,
                    'activity' => 'Unliked your comment',
                ]);
            } else {
                Comment::where('id', $request->comment_id)->increment('like', 1);

                Reaction::create([
                    'user_id'     => $request->user()->id,
                    'type'        => $request->type,
                    'comment_id'  => $request->comment_id,
                ]);

                Notification::create([
                    'who' => $request->user()->id,
                    'whom' => $request->whom,
                    'type' => 'comment',
                    'type_id' => $request->comment_id,
                    'post_id' => $request->post_id,
                    'comment_id' => $request->comment_id,
                    'activity' => 'Liked your comment',
                ]);
            }
    
            return response()->json([
                'status'  => 200
            ]);
        } catch(\Exception $e) {
            return response()->json([
                'status'  => 500,
                'message' => $e
            ]);
        }
    }

    public function dislikePost(Request $request) {
        try {

            $post = Post::find($request->post_id);
            $exists = Reaction::where('post_id', $request->post_id)->where('user_id', $request->user()->id)->first();
            
            if($exists) {
                $post->decrement('dislike', 1);
                $exists->delete();
            } else {
                $post->increment('dislike', 1);

                Reaction::create([
                    'user_id'   => $request->user()->id,
                    'type'      => $request->type,
                    'post_id'   => $request->post_id,
                ]);

                Notification::create([
                    'who' => $request->user()->id,
                    'whom' => $post->user_id,
                    'type' => 'post',
                    'type_id' => $request->post->id,
                    'post_id' => $request->post_id,
                    'activity' => 'Disliked your Post',
                ]);
            }
            
            return response()->json([
                'status'  => 200
            ]);
        } catch(\Exception $e) {
            return response()->json([
                'status'  => 500,
                'message' => $e
            ]);
        }
    }

    public function dislikeComment(Request $request) {
        try {
            $post = Post::find($request->post_id);
            $exists = Reaction::where('comment_id', $request->comment_id)->where('type', 'dislike')->where('user_id', $request->user()->id)->first();
            
            if($exists) {
                Comment::where('id', $request->comment_id)->decrement('dislike', 1);
                $exists->delete();

                Notification::create([
                    'who' => $request->user()->id,
                    'whom' => $post->user_id,
                    'type' => 'comment',
                    'type_id' => $request->comment_id,
                    'post_id' => $post->id,
                    'comment_id' => $request->comment_id,
                    'activity' => 'Undisliked your comment',
                ]);
            } else {
                Comment::where('id', $request->comment_id)->increment('dislike', 1);
                Reaction::create([
                    'user_id'      => $request->user()->id,
                    'type'         => $request->type,
                    'comment_id'   => $request->comment_id,
                ]);
                Notification::create([
                    'who' => $request->user()->id,
                    'whom' => $post->user_id,
                    'type' => 'comment',
                    'type_id' => $request->comment_id,
                    'post_id' => $post->id,
                    'comment_id' => $request->comment_id,
                    'activity' => 'Disliked your comment',
                ]);
            }
    
            return response()->json([
                'status'  => 200
            ]);
        } catch(\Exception $e) {
            return response()->json([
                'status'  => 500,
                'message' => $e
            ]);
        }
    }

    public function comment(Request $request) {
        $validate = Validator::make($request->all(), [
            'content'  => 'required'
        ]);

        if($validate->fails()) {
            return response()->json([
                'status' => 400,
                'errors' => $validate->errors()
            ]);
        }

        $post = Post::find($request->post_id);

        try {
            if(!$request->comment_id) {
                $comment = Comment::create([
                    'post_id'    => $request->post_id,
                    'user_to'    => $post->user_id,
                    'user_id'    => $request->user()->id,
                    'content'    => json_encode($request->content),
                    'status'     => $request->status,
                    'like'       => 0,
                    'dislike'    => 0,
                    'comment'    => 0,
                    'report'     => 0,
                    'reply'      => 0
                ]);

                Post::where('id', $request->post_id)->increment('comment', 1);

                Notification::create([
                    'who' => $request->user()->id,
                    'whom' => $post->user_id,
                    'type' => 'comment',
                    'type_id' => $comment->id,
                    'post_id' => $request->post_id,
                    'comment_id' => $comment->id,
                    'activity' => 'Commented on your Post',
                ]);
            } else {
                $comment = Comment::create([
                    'post_id'    => $request->post_id,
                    'user_to'    => $post->user_id,
                    'user_id'    => $request->user()->id,
                    'comment_id' => $request->comment_id,
                    'content'    => json_encode($request->content),
                    'status'     => $request->status,
                    'like'       => 0,
                    'dislike'    => 0,
                    'comment'    => 0,
                    'report'     => 0,
                    'reply'      => 0
                ]);

                Comment::where('id', $request->comment_id)->increment('reply', 1);

                Notification::create([
                    'who' => $request->user()->id,
                    'whom' => $post->user_id,
                    'type' => 'comment',
                    'type_id' => $comment->id,
                    'post_id' => $request->post_id,
                    'comment_id' => $comment->id,
                    'activity' => 'Replied your comment',
                ]);
            }
    
    
            return response()->json([
                'status'  => 200
            ]);
        } catch(\Exception $e) {
            return response()->json([
                'status'  => 500,
                'message' => $e
            ]);
        }
    }

    public function replies($id) {
        $replies = Comment::with('user', 'user.personalInfo', 'userReplyTo')->where('comment_id', $id)->get();

        return response()->json([
           'status'  => 200,
           'comment_id' => $id,
           'replies' => $replies
        ]);
    }

    public function notification() {
        Notification::create([
            'user_id' => $request->user()->id,
            'type' => 'comment',
            'type_id' => $request->comment_id,
            'activity' => $request->user()->id.' Disliked your comment',
        ]);
    }

    public function deleteMessage($id)
    {
        $message = Message::find($id);

        if(!$message) {
            return response()->json([
                'status'   => 404,
                'message'  => 'No Data Found !'
            ]);    
        }

        \DB::transaction(function() use($message) {
            if ($message->file && \Storage::disk('public')->exists($message->file)) {
                \Storage::disk('public')->delete($message->file);
            }
    
            $message->delete();
        });

        return response()->json([
            'status'   => 200
        ]);
    }

    public function deleteComment($id)
    {
        \DB::transaction(function() use($id) {
            $comment = Comment::find($id);

            if(!$comment) {
                return response()->json([
                    'status'   => 404,
                    'message'  => 'No Data Found !'
                ]);    
            }

            $comment->delete();

            
            if($comment->comment_id== null) {
                Post::where('id', $comment->post_id)->decrement('comment', 1);
            } else {
                Comment::where('id', $comment->comment_id)->decrement('reply', 1);
            }

            return response()->json([
                'status'   => 200
            ]);
        });
    }    

    public function getLikes($type, $id) 
    {
        try {
            $likes = 0;
            
            if($type == 'p') {
                $likes = Post::where('id', $id)->first()->like;
            } else if($type == 'c') {
                $likes = Comment::where('id', $id)->first()->like;
            }

            return response()->json([
                'status'   => 200,
                'likes'    => $likes
            ]);
        } catch(\Exception $e) {
            return response()->json([
                'status'   => 500
            ]);
        }
    }

    public function getDislikes($type, $id) 
    {
        try {
            $dislikes = 0;
            
            if($type == 'p') {
                $dislikes = Post::where('id', $id)->first()->dislike;
            } else if($type == 'c') {
                $dislikes = Comment::where('id', $id)->first()->dislike;
            }

            return response()->json([
                'status'   => 200,
                'dislikes'    => $dislikes
            ]);
        } catch(\Exception $e) {
            return response()->json([
                'status'   => 500
            ]);
        }
    }

    public function reactionUserList($type, $id) 
    {
        try {
            $users = [];
            
            if($type == 'p') {
                $reactions = Reaction::with('user')->where('post_id', $id)->where('type', 'like')->get();
            } else if($type == 'c') {
                $reactions = Reaction::with('user')->where('comment_id', $id)->where('type', 'like')->get();
            }

            return response()->json([
                'status'     => 200,
                'users'  => ReactionUserResource::collection($reactions)
            ]);
        } catch(\Exception $e) {
            return response()->json([
                'status'   => 500
            ]);
        }
    }

    public function getNewNotifications()
    {
        try {
            $notifications['activities'] = Notification::where('is_read', 'no')->where('whom', auth()->id())->orderBy('created_at', 'DESC')->count();
            $notifications['messages'] = Message::where('receiver', auth()->id())->where('status', 'delivered')->orderBy('created_at', 'DESC')->count();
            $notifications['friends'] = Connection::with('user')->where('request_type', 'friend')->where('status', 'pending')->where('receiver', auth()->id())->count();
            $notifications['cases'] = CaseNotification::with('case', 'who')->where('status', 'delivered')->where('whom', auth()->id())->count();

            return response()->json([
                'status'        => 200,
                'notifications' => $notifications
            ]);
        } catch(\Exception $e) {
            return response()->json([
                'status'     => 500
            ]);
        }
    }

    public function getNotifications(Request $request)
    {
        try {
            $limit = $request->get('limit', 10); // Default to 10 items per request
            $offset = $request->get('offset', 0); // Default to 0 for the first batch
            $notifications = Notification::with(['userWho', 'userWhom', 'post', 'comment'])
            ->where('whom', auth()->id())
            ->orderBy('created_at', 'DESC')
            ->skip($offset)
            ->take($limit)
            ->get();

            return response()->json([
                'status'        => 200,
                'list' => $notifications
            ]);
        } catch(\Exception $e) {
            return response()->json([
                'status'     => 500
            ]);
        }
    }

    public function getCaseNotifications(Request $request)
    {
        try {
            $limit = $request->get('limit', 10); // Default to 10 items per request
            $offset = $request->get('offset', 0); // Default to 0 for the first batch
            $notifications = CaseNotification::with(['who', 'case'])
            ->where('whom', auth()->id())
            ->orderBy('created_at', 'DESC')
            ->skip($offset)
            ->take($limit)
            ->get();

            return response()->json([
                'status'        => 200,
                'list' => $notifications
            ]);
        } catch(\Exception $e) {
            return response()->json([
                'status'     => 500
            ]);
        }
    }

    public function readNotifications()
    {
        try {
            $unreads = Notification::where('whom', auth()->id())->where('is_read', 'no')->get();
            foreach($unreads as $notification) {
                Notification::where('id', $notification->id)->update([
                    'is_read' => 'yes',
                ]);
            }

            return response()->json([
                'status'        => 200,
                'list' => $notifications
            ]);
        } catch(\Exception $e) {
            return response()->json([
                'status'     => 500
            ]);
        }
    }

    public function readCaseNotifications()
    {
        try {
            $unreads = CaseNotification::where('whom', auth()->id())->where('status', 'delivered')->get();
            foreach($unreads as $notification) {
                CaseNotification::where('id', $notification->id)->update([
                    'status' => 'seen',
                ]);
            }

            return response()->json([
                'status'        => 200,
                'list' => $notifications
            ]);
        } catch(\Exception $e) {
            return response()->json([
                'status'     => 500
            ]);
        }
    }

    public function readChats()
    {
        try {
            $unreads = Message::where('receiver', auth()->id())->where('status', 'delivered')->get();
            foreach($unreads as $message) {
                Message::where('id', $message->id)->update([
                    'status' => 'unseen',
                ]);
            }

            return response()->json([
                'status'        => 200
            ]);
        } catch(\Exception $e) {
            return response()->json([
                'status'     => 500
            ]);
        }
    }

    public function readMessages()
    {
        try {
            $unseens = Message::where('receiver', auth()->id())->where('status', 'unseen')->get();
            foreach($unseens as $message) {
                Message::where('id', $message->id)->update([
                    'status' => 'seen',
                ]);
            }

            return response()->json([
                'status'        => 200
            ]);
        } catch(\Exception $e) {
            return response()->json([
                'status'     => 500
            ]);
        }
    }

    public function postView($id) 
    {
        try {
            $post = Post::where('id', $id)->increment('views', 1);
            return response()->json([
                'status'   => 200
            ]);
        } catch(\Exception $e) {
            return response()->json([
                'status'     => 500
            ]);
        }
    }

    private function sendNotificationToUser($notification, $userId)
    {
        broadcast(new NewNotification($notification->activity, $userId));
        return response()->json(['status' => 'Notification sent']);
    }
}
