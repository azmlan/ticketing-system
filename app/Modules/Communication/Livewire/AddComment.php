<?php

namespace App\Modules\Communication\Livewire;

use App\Modules\Communication\Events\CommentCreated;
use App\Modules\Communication\Models\Comment;
use App\Modules\Communication\Models\ResponseTemplate;
use App\Modules\Tickets\Services\RichTextSanitizer;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Component;

class AddComment extends Component
{
    public string $ticketUlid = '';
    public string $body = '';
    public bool $isInternal = true;
    public string $selectedTemplate = '';

    public function mount(string $ticketUlid): void
    {
        if (! auth()->check()) {
            abort(403);
        }

        $this->ticketUlid = $ticketUlid;
    }

    public function updatedSelectedTemplate(string $value): void
    {
        if (! $value) {
            return;
        }

        $template = ResponseTemplate::active()->find($value);
        if (! $template) {
            return;
        }

        $locale      = app()->getLocale();
        $this->body  = $locale === 'ar' ? $template->body_ar : $template->body_en;
        $this->isInternal = $template->is_internal;
    }

    public function submit(): void
    {
        $user = auth()->user();

        if (! $user) {
            abort(403);
        }

        $isEmployee = ! $user->is_tech && ! $user->is_super_user && ! $user->hasPermission('ticket.view-all');

        if ($isEmployee && $this->isInternal) {
            abort(403);
        }

        $key   = 'comment.create:' . $user->id;
        $max   = config('rate_limits.comment_create.max_attempts', 30);
        $decay = config('rate_limits.comment_create.decay_seconds', 3600);

        if (RateLimiter::tooManyAttempts($key, $max)) {
            abort(429);
        }

        $this->validate([
            'body' => ['required', 'string', 'max:10000'],
        ]);

        $sanitized  = app(RichTextSanitizer::class)->sanitize($this->body);
        $isInternal = $isEmployee ? false : $this->isInternal;

        $comment = Comment::create([
            'ticket_id'   => $this->ticketUlid,
            'user_id'     => $user->id,
            'body'        => $sanitized,
            'is_internal' => $isInternal,
        ]);

        RateLimiter::hit($key, $decay);

        event(new CommentCreated(
            ticketId: $this->ticketUlid,
            commentId: $comment->id,
            isInternal: $comment->is_internal,
        ));

        $this->body             = '';
        $this->isInternal       = true;
        $this->selectedTemplate = '';
    }

    public function render()
    {
        $locale = app()->getLocale();

        $templates = ResponseTemplate::active()->get()->map(fn ($t) => [
            'id'    => $t->id,
            'title' => $locale === 'ar' ? $t->title_ar : $t->title_en,
        ]);

        $comments = Comment::where('ticket_id', $this->ticketUlid)
            ->with('author')
            ->orderBy('created_at')
            ->get();

        return view('livewire.communication.add-comment', compact('templates', 'comments'));
    }
}
