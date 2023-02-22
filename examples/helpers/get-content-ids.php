<?php 

function getContentIdsFromEnv() {
    return explode(',', getenv('CONTENT_IDS'));
}