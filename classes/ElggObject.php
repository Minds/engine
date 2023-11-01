<?php
/**
 * Elgg Object
 *
 * Elgg objects are the most common means of storing information in the database.
 * They are a child class of ElggEntity, so receive all the benefits of the Entities,
 * but also include a title and description field.
 *
 * An ElggObject represents a row from the objects_entity table, as well
 * as the related row in the entities table as represented by the parent
 * ElggEntity object.
 *
 * @internal Title and description are stored in the objects_entity table.
 *
 * @package    Elgg.Core
 * @subpackage DataModel.Object
 *
 * @property string $title       The title, name, or summary of this object
 * @property string $description The body, description, or content of the object
 * @property array  $tags        Array of tags that describe the object
 */
class ElggObject extends ElggEntity {

	/**
	 * Initialise the attributes array to include the type,
	 * title, and description.
	 *
	 * @return void
	 */
	protected function initializeAttributes() {
		parent::initializeAttributes();

		$this->attributes['type'] = "object";
		$this->attributes['title'] = NULL;
		$this->attributes['description'] = NULL;
		$this->attributes['time_created'] = time();
		$this->attributes['time_updated'] = time();
	}

	/**
	 * Load or create a new ElggObject.
	 *
	 * If no arguments are passed, create a new entity.
	 *
	 * If an argument is passed, attempt to load a full ElggObject entity.
	 * Arguments can be:
	 *  - The GUID of an object entity.
	 *  - A DB result object from the entities table with a guid property
	 *
	 * @param mixed $guid If an int, load that GUID.  If a db row, then will attempt to
	 * load the rest of the data.
	 *
	 * @throws IOException If passed an incorrect guid
	 * @throws InvalidParameterException If passed an Elgg* Entity that isn't an ElggObject
	 */
	function __construct($guid = null) {
		$this->initializeAttributes();

		parent::__construct($guid);
	}

	/**
	 * Loads the full ElggObject when given a guid.
	 *
	 * @param mixed $guid GUID of an ElggObject or the stdClass object from entities table
	 *
	 * @return bool
	 */
	protected function load($guid) {
		foreach($guid as $k => $v){
			$this->attributes[$k] = $v;
		}

		cache_entity($this);

		return true;
	}

	/*
	 * EXPORTABLE INTERFACE
	 */

	/**
	 * Return an array of fields which can be exported.
	 *
	 * @return array
	 */
	public function getExportableValues() {
		return array_merge(parent::getExportableValues(), array(
			'title',
			'description',
			'featured',
			'featured_id',
			'ownerObj',
			'category',
			'comments:count',
            'thumbs:up:count',
            'thumbs:up:user_guids',
            'thumbs:down:count',
            'thumbs:down:user_guids',
            'pinned'
		));
	}

}
