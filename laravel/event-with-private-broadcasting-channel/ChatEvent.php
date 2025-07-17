<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

use App\Models\User;
use App\Models\Chat;
use App\Models\Message;

use App\Http\Resources\MessageResource;

class ChatEvent implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $chatId;
    public $userId;

    public $messages;
    public $to;
    public $chat;

    public function __construct($chatId, $userId)
    {
        $this->chatId = $chatId;
        $this->userId = $userId;

        $this->messages = new MessageResource(Message::orderBy('created_at', 'DESC')->where('chat_id', $chatId)->first());

        $this->to       = User::where('id', $userId)->first();
        $this->chat     = Chat::where('id', $chatId)->first(); 
    }

    public function broadcastOn()
    {
        return new PrivateChannel('private-chat-' . $this->userId);
    }
}


