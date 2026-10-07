<?php

use App\Actions\Comments\AddComment;
use App\Models\Item;
use App\Models\Project;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Validation\ValidationException;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->item = Item::factory()->for(Project::factory()->withMember($this->user))->create();
});

it('adds a comment and records it', function () {
    $comment = app(AddComment::class)->handle($this->user, $this->item, ['body' => 'Kan ik reproduceren.']);

    expect($comment)->user_id->toBe($this->user->id)->body->toBe('Kan ik reproduceren.')
        ->and($this->item->activities()->sole())
        ->event->toBe('commented')
        ->new->toBe(['comment_id' => $comment->id]);
});

it('requires a body', function () {
    app(AddComment::class)->handle($this->user, $this->item, ['body' => '']);
})->throws(ValidationException::class);

it('refuses non-members', function () {
    app(AddComment::class)->handle(User::factory()->create(), $this->item, ['body' => 'Hoi']);
})->throws(AuthorizationException::class);
