<?php

use Minds\Core\Di\Di;

/**
 * This class represents a physical file.
 *
 * Create a new ElggFile object and specify a filename, and optionally a
 * FileStore (if one isn't specified then the default is assumed.)
 *
 * Open the file using the appropriate mode, and you will be able to
 * read and write to the file.
 *
 * Optionally, you can also call the file's save() method, this will
 * turn the file into an entity in the system and permit you to do
 * things like attach tags to the file etc. This is not done automatically
 * since there are many occasions where you may want access to file data
 * on datastores using the ElggFile interface but do not want to create
 * an Entity reference to it in the system (temporary files for example).
 *
 * @class      ElggFile
 * @package    Elgg.Core
 * @subpackage DataModel.File
 */
class ElggFile extends ElggObject {
	/** Filestore */
	private $filestore;

	/** File handle used to identify this file in a filestore. Created by open. */
	private $handle;

	/**
	 * Set subtype to 'file'.
	 *
	 * @return void
	 */
	protected function initializeAttributes() {
		parent::initializeAttributes();

		$this->attributes['subtype'] = "file";
	}

	/**
	 * Loads an ElggFile entity.
	 *
	 * @param int $guid GUID of the ElggFile object
	 */
	public function __construct($guid = null) {
		parent::__construct($guid);

		// Set default filestore
		$this->filestore = $this->getFilestore();
	}

	/**
	 * Set the filename of this file.
	 *
	 * @param string $name The filename.
	 *
	 * @return void
	 */
	public function setFilename($name) {
		$this->filename = $name;
	}

	/**
	 * Return the filename.
	 *
	 * @return string
	 */
	public function getFilename() {
		return $this->filename;
	}

	/**
	 * Return the filename of this file as it is/will be stored on the
	 * filestore, which may be different to the filename.
	 *
	 * @return string
	 */
	public function getFilenameOnFilestore() {
		return $this->filestore->getFilenameOnFilestore($this);
	}

	/**
	 * Return the size of the filestore associated with this file
	 *
	 * @param string $prefix         Storage prefix
	 * @param int    $container_guid The container GUID of the checked filestore
	 *
	 * @return int
	 */
	public function getFilestoreSize($prefix = '', $container_guid = 0) {
		if (!$container_guid) {
			$container_guid = $this->container_guid;
		}
		$fs = $this->getFilestore();
		// @todo add getSize() to ElggFilestore
		return $fs->getSize($prefix, $container_guid);
	}

	/**
	 * Get the mime type of the file.
	 *
	 * @return string
	 */
	public function getMimeType() {
		if ($this->mimetype) {
			return $this->mimetype;
		}

		// @todo Guess mimetype if not here
	}

	/**
	 * Set the mime type of the file.
	 *
	 * @param string $mimetype The mimetype
	 *
	 * @return bool
	 */
	public function setMimeType($mimetype) {
		return $this->mimetype = $mimetype;
	}

	/**
	 * Set the optional file description.
	 *
	 * @param string $description The description.
	 *
	 * @return bool
	 */
	public function setDescription($description) {
		$this->description = $description;
	}

	/**
	 * Open the file with the given mode
	 *
	 * @param string $mode Either read/write/append
	 *
	 * @return resource File handler
	 *
	 * @throws IOException|InvalidParameterException
	 */
	public function open($mode) {
		if (!$this->getFilename()) {
			throw new IOException('IOException:MissingFileName');
		}

		// See if file has already been saved
		// seek on datastore, parameters and name?

		// Sanity check
		if (
			($mode != "read") &&
			($mode != "write") &&
			($mode != "append")
		) {
			$msg = 'InvalidParameterException:UnrecognisedFileMode ' . $mode;
			throw new InvalidParameterException($msg);
		}

		// Get the filestore
		$fs = $this->getFilestore();

		// Open the file handle
		$this->handle = $fs->open($this, $mode);

		return $this->handle;
	}

	/**
	 * Write data.
	 *
	 * @param string $data The data
	 *
	 * @return bool
	 */
	public function write($data) {
		$fs = $this->getFilestore();

		return $fs->write($this->handle, $data);
	}

	/**
	 * Read data.
	 *
	 * @param int $length Amount to read.
	 * @param int $offset The offset to start from.
	 *
	 * @return mixed Data or false
	 */
	public function read($length = 0, $offset = 0) {
		$fs = $this->getFilestore();

		return $fs->read($this->handle, $length, $offset);
	}

	/**
	 * Gets the full contents of this file.
	 *
	 * @return mixed The file contents.
	 */
	public function grabFile() {
		$fs = $this->getFilestore();
		return $fs->grabFile($this);
	}

	/**
	 * Close the file and commit changes
	 *
	 * @return bool
	 */
	public function close() {
		$fs = $this->getFilestore();

		if ($fs->close($this->handle)) {
			$this->handle = NULL;

			return true;
		}

		return false;
	}

	/**
	 * Delete this file.
	 *
	 * @return bool
	 */
	public function delete() {
		$fs = $this->getFilestore();
		//if ($fs->delete($this)) {
			return parent::delete();
		//}
	}

	/**
	 * Seek a position in the file.
	 *
	 * @param int $position Position in bytes
	 *
	 * @return bool
	 */
	public function seek($position) {
		$fs = $this->getFilestore();

		// @todo add seek() to ElggFilestore
		return $fs->seek($this->handle, $position);
	}

	/**
	 * Return the current position of the file.
	 *
	 * @return int The file position
	 */
	public function tell() {
		$fs = $this->getFilestore();

		return $fs->tell($this->handle);
	}

	/**
	 * Return the size of the file in bytes.
	 *
	 * @return int
	 */
	public function size() {
		return $this->filestore->getFileSize($this);
	}

	/**
	 * Return a boolean value whether the file handle is at the end of the file
	 *
	 * @return bool
	 */
	public function eof() {
		$fs = $this->getFilestore();

		return $fs->eof($this->handle);
	}

	/**
	 * Returns if the file exists
	 *
	 * @return bool
	 */
	public function exists() {
		$fs = $this->getFilestore();

		return $fs->exists($this);
	}

	/**
	 * Return a filestore suitable for saving this file.
	 * This filestore is either a pre-registered filestore,
	 * a filestore as recorded in metadata or the system default.
	 *
	 * @return ElggFilestore
	 */
	protected function getFilestore()
	{
		if (!$this->filestore) {
			$this->filestore = new ElggDiskFilestore(Di::_()->get('Config')->get('dataroot'));
		}
		
		return $this->filestore;
	}

}
