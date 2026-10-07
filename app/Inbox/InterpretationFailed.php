<?php

namespace App\Inbox;

use RuntimeException;

/**
 * The model answered, but not with something usable (refusal, truncation, bad JSON).
 * Retrying will not help, so the job marks the message as failed straight away.
 */
class InterpretationFailed extends RuntimeException {}
