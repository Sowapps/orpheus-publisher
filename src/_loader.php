<?php
/**
 * Loader File for the publisher sources
 */

use Orpheus\EntityDescriptor\Entity\PermanentEntity;

/**
 * Get the id whatever we give to it
 *
 */
function id(string|PermanentEntity &$id): string {
	return $id = is_object($id) ? $id->id() : $id;
}
