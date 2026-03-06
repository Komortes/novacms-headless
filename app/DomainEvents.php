<?php

namespace App;

final class DomainEvents
{
    public const CONTENT_UPDATED = 'content.updated';
    public const SUMMARY_GENERATED = 'summary.generated';
    public const SUMMARY_FAILED = 'summary.failed';
    public const SUMMARY_CANCELLED = 'summary.cancelled';
    public const EMBEDDING_CREATED = 'embedding.created';
}
