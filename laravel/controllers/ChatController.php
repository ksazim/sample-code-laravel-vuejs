<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Chat;
use App\Models\Message;
use App\Models\User;
use App\Http\Resources\ChatResource;
use App\Http\Resources\MessageResource;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use App\Events\ChatEvent;

class ChatController extends Controller
{
    public function chat(Request $request, $chatId) {
        $validate = Validator::make($request->all(), [
            'chat_id'  => 'required'
        ]);

        if($validate->fails()) {
            return response()->json([
                'status' => 400,
                'message' => 'Something went wrong'
            ]);
        }

        if(!$request->content && !$request->file) {
            return response()->json([
                'status' => 400,
                'message' => 'Nothing to Send'
            ]);
        }

        try {
            $file = null;

            if ($request->hasFile('file')) {
                $file = $this->handleFileUpload($request->file('file'), 'uploads/chat');
            }

            Message::create([
                'chat_id' => $chatId,
                'user_id' => auth()->id(),
                'receiver' => $request->receiver_id,
                'content' => $request->content,
                'file'    => $file,
                'status'  => 'delivered'
            ]);

            broadcast(new ChatEvent($chatId, $request->receiver_id));

            return response()->json([
                'status' => 200
            ]);
        } catch(\Exception $e) {
            return response()->json([
                'status' => 500
            ]);
        }
    }

    public function chatBox(Request $request, $chatId) {
        $chats = Message::with('user')
                        ->orderBy('created_at', 'DESC')
                        ->where('chat_id', $chatId)
                        ->get();
                        // ->groupBy(function ($message) {
                        //     return $message->created_at->format('Y-m-d'); // Group by date (e.g., 2024-01-01)
                        // });

        $chat = Chat::where('id', $chatId)->first();    
        $to = ($chat->initiator_id == auth()->id()) ? $chat->receiver_id : $chat->initiator_id;
        return response()->json([
            'status'   => 200,
            'messages' => MessageResource::collection($chats),
            'to'       => User::where('id', $to)->first()
        ]);
    }

    public function exist($receiverId) {
        try {
            $asReceiver = Chat::where('receiver_id', $receiverId)
                ->where('initiator_id', auth()->id())
                ->first();

            $asInitiator = Chat::where('initiator_id', $receiverId)
                ->where('receiver_id', auth()->id())
                ->first();

            if($asReceiver || $asInitiator) {
                if($asReceiver) {
                    return $asReceiver->id;
                }

                if($asInitiator) {
                    return $asInitiator->id;
                }
            }

            $chat = Chat::create([
                'initiator_id' => auth()->id(),
                'receiver_id' => $receiverId,
            ]);

            return $chat->id;
        } catch(\Exception $e) {
            return $e;
        }
    }

    public function chatList() {
        try {
            $list= Chat::with('initiator', 'receiver', 'lastMsg', 'newMessages')
            ->where('receiver_id', auth()->id())
            ->orWhere('initiator_id', auth()->id())
            ->get();

            return response()->json([
                'status' => 200,
                'list' => ChatResource::collection($list)
            ]);
        } catch(\Exception $e) {
            return response()->json([
                'status' => 500
            ]);
        }
    }

    private function handleFileUpload($file, $path) {
        $extension = $file->getClientOriginalExtension();
        $filename = time() . '.' . $extension;
        $file->storeAs($path, $filename, 'public');
        return $path . '/' . $filename;
    }
}
