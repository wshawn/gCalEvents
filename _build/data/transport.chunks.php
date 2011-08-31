<?php
$chunkArray = array();

/* create the chunk */
$chunk= $modx->newObject('modChunk');
$chunk->set('id',0);
$chunk->set('name', 'gcaleventsEventDefault');
$chunk->set('description', 'Default Google Calendar event template.');
$chunk->set('snippet',file_get_contents($sources['source_core'].'/chunks/gcalevents.event.chunk.tpl'));

$chunkArray[] = $chunk;

/* create another chunk */
$chunk= $modx->newObject('modChunk');
$chunk->set('id',0);
$chunk->set('name', 'gcaleventsWrapperDefault');
$chunk->set('description', 'Default Google Calendar events wrapper template.');
$chunk->set('snippet',file_get_contents($sources['source_core'].'/chunks/gcalevents.wrap.chunk.tpl'));

$chunkArray[] = $chunk;

return $chunkArray;