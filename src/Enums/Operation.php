<?php

declare(strict_types=1);

namespace AiModelUsageTracker\AiModelUsageTracker\Enums;

enum Operation: string
{
    case Chat = 'chat';
    case Embeddings = 'embeddings';
    case Image = 'image';
    case Audio = 'audio';
    case Transcription = 'transcription';
    case Rerank = 'rerank';
}
