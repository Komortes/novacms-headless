<?php

namespace App;

final class DomainEvents
{
    public const CONTENT_UPDATED = 'content.updated';
    public const SUMMARY_GENERATED = 'summary.generated';
    public const EMBEDDING_CREATED = 'embedding.created';
    public const SUMMARY_STATUS_CHANGED = 'summary.status.changed';
    public const EMBEDDING_STATUS_CHANGED = 'embedding.status.changed';
}
