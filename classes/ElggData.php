<?php
/**
 * A generic class that contains shared code b/w
 * ElggExtender, ElggEntity, and ElggRelationship
 *
 * @package    Elgg.Core
 * @subpackage DataModel
 *
 * @property int $owner_guid
 * @property int $time_created
 */
abstract class ElggData implements
	Iterator,	// Override foreach behaviour
	ArrayAccess, // Override for array access
	Exportable
{

	/**
	 * The main attributes of an entity.
	 * Holds attributes to save to database
	 * This contains the site's main properties (id, etc)
	 * Blank entries for all database fields should be created by the constructor.
	 * Subclasses should add to this in their constructors.
	 * Any field not appearing in this will be viewed as a
	 */
	protected $attributes = array();


	/**
	 * Initialize the attributes array.
	 *
	 * This is vital to distinguish between metadata and base parameters.
	 *
	 * @return void
	 */
	protected function initializeAttributes() {
		// Create attributes array if not already created
		if (!is_array($this->attributes)) {
			$this->attributes = array();
		}

		$this->attributes['time_created'] = NULL;
	}

	/**
	 * Return an attribute or a piece of metadata.
	 *
	 * @param string $name Name
	 *
	 * @return mixed
	 */
	public function __get($name) {
		return $this->get($name);
	}

	/**
	 * Set an attribute or a piece of metadata.
	 *
	 * @param string $name  Name
	 * @param mixed  $value Value
	 *
	 * @return mixed
	 */
	public function __set($name, $value) {
		return $this->set($name, $value);
	}

	/**
	 * Test if property is set either as an attribute or metadata.
	 *
	 * @tip Use isset($entity->property)
	 *
	 * @param string $name The name of the attribute or metadata.
	 *
	 * @return bool
	 */
	function __isset($name) {
		return $this->$name !== NULL;
	}

	/**
	 * Fetch the specified attribute
	 *
	 * @param string $name The attribute to fetch
	 *
	 * @return mixed The attribute, if it exists.  Otherwise, null.
	 */
	abstract protected function get($name);

	/**
	 * Set the specified attribute
	 *
	 * @param string $name  The attribute to set
	 * @param mixed  $value The value to set it to
	 *
	 * @return bool The success of your set function?
	 */
	abstract protected function set($name, $value);

	/**
	 * Get a URL for this object
	 *
	 * @return string
	 */
	abstract public function getURL();

	/**
	 * Returns the UNIX epoch time that this entity was created
	 *
	 * @return int UNIX epoch time
	 */
	public function getTimeCreated() {
		return $this->time_created;
	}

	/**
	 * Gets age of data by subtracting time created from the current time.
	 * @return int - age of the data.
	 */
	public function getAge(): int
	{
		return time() - $this->getTimeCreated();
	}

	/*
	 * ITERATOR INTERFACE
	 */

	/*
	 * This lets an entity's attributes be displayed using foreach as a normal array.
	 * Example: http://www.sitepoint.com/print/php5-standard-library
	 */
	protected $valid = FALSE;

	/**
	 * Return in array for the object..
	 */
	public function toArray(): array
    {
		$attrs = array();
		foreach($this->attributes as $k => $v){
			if(is_null($v))
				continue;

			if(is_array($v))
				$v = json_encode($v);

			$attrs[$k] = $v;
		}
		return $attrs;
	}

	/**
	 * Iterator interface
	 *
	 * @see Iterator::rewind()
	 *
	 * @return void
	 */
	public function rewind(): void
    {
		$this->valid = (FALSE !== reset($this->attributes));
	}

	/**
	 * Iterator interface
	 *
	 * @see Iterator::current()
	 *
	 * @return mixed|false
	 */
	public function current(): mixed
    {
		return current($this->attributes);
	}

    /**
     * Iterator interface
     *
     * @return int|string|null
     * @see Iterator::key()
     *
     */
	public function key(): int|string|null
    {
		return key($this->attributes);
	}

	/**
	 * Iterator interface
	 *
	 * @see Iterator::next()
	 *
	 * @return void
	 */
	public function next(): void
    {
		$this->valid = (FALSE !== next($this->attributes));
	}

	/**
	 * Iterator interface
	 *
	 * @see Iterator::valid()
	 *
	 * @return bool
	 */
	public function valid(): bool
    {
		return $this->valid;
	}

	/*
	 * ARRAY ACCESS INTERFACE
	 */

	/*
	 * This lets an entity's attributes be accessed like an associative array.
	 * Example: http://www.sitepoint.com/print/php5-standard-library
	 */

    /**
     * Array access interface
     *
     * @param mixed $offset Name
     * @param mixed $value Value
     *
     * @return void
     * @see ArrayAccess::offsetSet()
     *
     */
	public function offsetSet(mixed $offset, mixed $value): void
    {
		if (array_key_exists($offset, $this->attributes)) {
			$this->attributes[$offset] = $value;
		}
	}

	/**
	 * Array access interface
	 *
	 * @see ArrayAccess::offsetGet()
	 *
	 * @param mixed $offset Name
	 *
	 * @return mixed
	 */
	public function offsetGet(mixed $offset): mixed
    {
		if (array_key_exists($offset, $this->attributes)) {
			return $this->attributes[$offset];
		}
		return null;
	}

	/**
	 * Array access interface
	 *
	 * @see ArrayAccess::offsetUnset()
	 *
	 * @param mixed $offset Name
	 *
	 * @return void
	 */
	public function offsetUnset(mixed $offset): void
    {
		if (array_key_exists($offset, $this->attributes)) {
			// Full unsetting is dangerous for our objects
			$this->attributes[$offset] = "";
		}
	}

	/**
	 * Array access interface
	 *
	 * @see ArrayAccess::offsetExists()
	 *
	 * @param mixed $offset Offset
	 *
	 * @return bool
	 */
	public function offsetExists(mixed $offset): bool {
		return array_key_exists($offset, $this->attributes);
	}
}
