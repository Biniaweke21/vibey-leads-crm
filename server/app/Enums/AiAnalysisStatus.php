<?php

namespace App\Enums;

enum AiAnalysisStatus: string
{
    case Pending = 'pending';
    case Ready = 'ready';
}
