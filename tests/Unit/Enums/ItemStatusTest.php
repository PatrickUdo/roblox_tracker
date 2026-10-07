<?php

use App\Enums\ItemStatus;

it('allows exactly the documented transitions', function (ItemStatus $from, ItemStatus $to, bool $allowed) {
    expect($from->canTransitionTo($to))->toBe($allowed);
})->with([
    'open → in_progress' => [ItemStatus::Open, ItemStatus::InProgress, true],
    'in_progress → done' => [ItemStatus::InProgress, ItemStatus::Done, true],
    'done → closed' => [ItemStatus::Done, ItemStatus::Closed, true],
    'in_progress → open' => [ItemStatus::InProgress, ItemStatus::Open, true],
    'done → open' => [ItemStatus::Done, ItemStatus::Open, true],
    'closed → open' => [ItemStatus::Closed, ItemStatus::Open, true],
    'open → done' => [ItemStatus::Open, ItemStatus::Done, false],
    'open → closed' => [ItemStatus::Open, ItemStatus::Closed, false],
    'in_progress → closed' => [ItemStatus::InProgress, ItemStatus::Closed, false],
    'done → in_progress' => [ItemStatus::Done, ItemStatus::InProgress, false],
    'closed → done' => [ItemStatus::Closed, ItemStatus::Done, false],
    'open → open' => [ItemStatus::Open, ItemStatus::Open, false],
]);

it('lists allowed transitions', function () {
    expect(ItemStatus::Done->allowedTransitions())->toBe([ItemStatus::Open, ItemStatus::Closed]);
});
