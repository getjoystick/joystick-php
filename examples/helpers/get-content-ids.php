<?php

declare(strict_types=1);

function getContentIdsFromEnv()
{
    return explode(',', getenv('CONTENT_IDS'));
}
