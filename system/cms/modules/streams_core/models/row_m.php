<?php defined('BASEPATH') or exit('No direct script access allowed');

/**
 * PyroStreams Row Model
 *
 * @package		PyroCMS\Core\Modules\Streams Core\Models
 * @author		Parse19
 * @copyright	Copyright (c) 2011 - 2012, Parse19
 * @license		http://parse19.com/pyrostreams/docs/license
 * @link		http://parse19.com/pyrostreams
 */
class Row_m extends MY_Model {

	/**
	 * Field Types to Ignore
	 *
	 * An array of the default columns
	 * that are created in every stream table
	 * that we don't need to include in
	 * some processes.
	 *
	 * @access 	public
	 * @var 	array
	 */
	public $ignore = array('id', 'created', 'updated', 'created_by');

	// --------------------------------------------------------------------------
				
	/**
	 * Cycle Select String
	 *
	 * @access 	public
	 * @var 	string
	 */
	public $data;

	// --------------------------------------------------------------------------
		
	/**
	 * Base Prefix
	 *
	 * Convenience Var
	 *
	 * @access 	public
	 * @var 	string
	 */
	public $base_prefix;

	// --------------------------------------------------------------------------
			
	/**
	 * Cycle Select String
	 *
	 * Each of the arrays can also be a string,
	 * in which case they will not be imploded.
	 *
	 * @access 	public
	 * @var 	string
	 */
	public $sql = array(
		'select'	=> array(), 	// will be joined by ','
		'where'		=> array(),		// will be joined by 'AND'
		'from'		=> array(),		// array of tables
		'order_by'	=> array(),		// will be joined by ','
		'misc'		=> array()		// will be joined by line breaks
	);
	
	// --------------------------------------------------------------------------
			
	/**
	 * All fields (so we don't have)
	 * to keep grabbing them from
	 * the database.
	 *
	 * @access 	public
	 * @var 	obj
	 */
	public $all_fields;
	
	// --------------------------------------------------------------------------
			
	/**
	 * Streams structure
	 *
	 * @access 	public
	 * @var 	array
	 */
	public $structure;
	
	// --------------------------------------------------------------------------

	/**
	 * Array of IDs called, by stream.
	 * Used to exclude IDs already called.
	 * @since 2.1
	 */
	public $called;
	
	// --------------------------------------------------------------------------

	/**
	 * Hook for get_rows
	 *
	 * @param 	public
	 * @var 	array($obj, $method_name)
	 */
	public $get_rows_hook 	= array();

	// --------------------------------------------------------------------------
	
	/**
	 * Data to send to the function
	 *
	 * @access	public
	 * @var		obj
	 */
	public $get_rows_hook_data;

	// --------------------------------------------------------------------------

	/**
	 * Get rows from a stream
	 *
	 * @return 	array 
	 * @param	array
	 * @param	obj
	 * @param	obj
	 * @param	obj
	 * @return	array
	 */
	public function get_rows($params, $fields, $stream)
	{
		$return = array();

		// First, let's get all out fields. That's
		// right. All of them.
		$this->all_fields = $this->fields_m->get_all_fields();
		
		// Now the structure. We will need this as well.
		$this->structure = $this->gather_structure();
	
		// So we don't get things confused
		if (isset($params['stream']))
		{
			unset($params['stream']);
		}

		// -------------------------------------
		// Extract Our Params
		// -------------------------------------

		extract($params, EXTR_OVERWRITE);

		// -------------------------------------
		// Set the site_ref
		// -------------------------------------
		// Allows you to use streams from other
		// sites on a multi-site managed site.
		// -------------------------------------

		if ( ! isset($site_ref)) $site_ref = SITE_REF;

		$this->db->set_dbprefix($site_ref.'_');

		// -------------------------------------
		// Convenience Vars
		// -------------------------------------
		
		$this->data->stream = $stream;

		$this->select_prefix 	= $this->db->protect_identifiers($stream->stream_prefix.$stream->stream_slug, true).'.';
		
		// -------------------------------------
		// Start Query Build
		// -------------------------------------
		
		// We may build on this.
		$this->sql['select'][] = $this->db->protect_identifiers($stream->stream_prefix.$stream->stream_slug, true).'.*';

		// -------------------------------------
		// From
		// -------------------------------------
		
		$this->sql['from'][] = $this->db->protect_identifiers($stream->stream_slug, true);

		// -------------------------------------
		// Get the day.
		// For calendars and stuff
		// -------------------------------------

		if (isset($get_day) and $get_day == true)
		{
			$this->sql['select'][] = 'DAY('.$this->select_prefix.$this->db->protect_identifiers($date_by).') as pyrostreams_cal_day';
		}
	
		// -------------------------------------
		// Disable
		// -------------------------------------
		// Allows users to turn off relationships
		// and created_by to save some queries
		// -------------------------------------
		
		if (isset($disable) and $disable)
		{		
			// Can be pre-processed
			if ( ! is_array($disable))
			{
				$disable = explode('|', $disable);
			}
		}	
		else
		{
			$disable = array();
		}

		// -------------------------------------
		// Ordering and Sorting
		// -------------------------------------

		if (isset($sort) and $sort == 'random')
		{
			// If we are doing sort by random,
			// it is a string since it is the only one
			$this->sql['order_by'] = 'RAND()';
		}
		else
		{
			// Default Sort. This should be set beforehand,
			// but setting it here is a last resort
			if ( ! isset($sort) or $sort == '')
			{
				$sort = 'ASC';
			}
	
			// Other sorting options
			if ( ! isset($order_by) or $order_by == '')
			{
				// Let's go with the stream setting now
				// since there isn't an override	
				if ($stream->sorting == 'title' and $stream->title_column)
				{
					$this->sql['order_by'][] = $this->select_prefix.$this->db->protect_identifiers($stream->title_column).' '.strtoupper($sort);	
				}
				elseif ($stream->sorting == 'custom')
				{
					$this->sql['order_by'][] = $this->select_prefix.$this->db->protect_identifiers('ordering_count').' '.strtoupper($sort);
				}
			}
			else
			{
				$this->sql['order_by'][] = $this->select_prefix.$this->db->protect_identifiers($order_by).' '.strtoupper($sort);
			}
		}

		// -------------------------------------
		// Exclude
		// -------------------------------------
		
		// Do we have anything in the excludes that we can add?
		if (isset($exclude_called) and $exclude_called == 'yes' and 
			isset($this->called[$stream->stream_slug]) and ! empty($this->called[$stream->stream_slug]))
		{
			$exclude .= '|'.implode('|', $this->called[$stream->stream_slug]);
		}
		
		if (isset($exclude) and $exclude)
		{		
			$exclusions = explode('|', $exclude);
			
			foreach ($exclusions as $exclude_id)
			{
				$this->sql['where'][] = $this->select_prefix.$this->db->protect_identifiers($exclude_by).' !='.$this->db->escape($exclude_id);
			}
		}

		// -------------------------------------
		// Include
		// -------------------------------------
		
		if (isset($include) and $include)
		{
			$inclusions = explode('|', $include);
			
			foreach ($inclusions as $include_id)
			{
				$this->sql['where'][] = $this->select_prefix.$this->db->protect_identifiers($include_by).' !='.$this->db->escape($include_id);
			}
		}

		// -------------------------------------
		// Where (Legacy)
		// -------------------------------------

		if (isset($where) and $where)
		{
			// Replace the segs
			
			$seg_markers 	= array('seg_1', 'seg_2', 'seg_3', 'seg_4', 'seg_5', 'seg_6', 'seg_7');
			$seg_values		= array($this->uri->segment(1), $this->uri->segment(2), $this->uri->segment(3), $this->uri->segment(4), $this->uri->segment(5), $this->uri->segment(6), $this->uri->segment(7));
		
			$where = str_replace($seg_markers, $seg_values, $where);
			
			$vals = explode('==', trim($w));
			
			if (count($vals) == 2)
			{
				$this->sql['where'][] = $this->select_prefix.$this->db->protect_identifiers($vals[0]).' !='.$this->db->escape($vals[1]);
			}
		}

		// -------------------------------------
		// Where (Current)
		// -------------------------------------

		// @todo
		
		// -------------------------------------
		// Show Upcoming
		// -------------------------------------
		// @todo - check to see if this is a
		// mysql date or a UNIX one.
		// -------------------------------------

		if (isset($show_upcoming) and $show_upcoming == 'no')
		{
			$this->sql['where'][] = $this->select_prefix.$this->db->protect_identifiers($date_by).' <= CURDATE()';
		}

		// -------------------------------------
		// Show Past
		// -------------------------------------
		// @todo - check to see if this is a
		// mysql date or a UNIX one.
		// -------------------------------------

		if (isset($show_past) and $show_past == 'no')
		{
			$this->sql['where'][] = $this->select_prefix.$this->db->protect_identifiers($date_by).' >= CURDATE()';
		}

		// -------------------------------------
		// Month / Day / Year
		// -------------------------------------
		
		$date_by_protected = $this->db->protect_identifiers($date_by);

		if (isset($year) and is_numeric($year))
		{
			$this->sql['where'][] = 'YEAR('.$this->select_prefix.$date_by_protected.')='.$this->db->escape($year);
		}

		if (isset($month) and is_numeric($month))
		{
			$this->sql['where'][] = 'MONTH('.$this->select_prefix.$date_by_protected.')='.$this->db->escape($month);
		}

		if (isset($day) and is_numeric($day))
		{
			$this->sql['where'][] = 'DAY('.$this->select_prefix.$date_by_protected.')='.$this->db->escape($day);
		}

		// -------------------------------------
		// Restrict User
		// -------------------------------------
		
		if (isset($restrict_user) and $restrict_user)
		{
			if ($restrict_user != 'no')
			{
				// Should we restrict to the current user?
				if ($restrict_user == 'current')
				{
					// Check and see if a user is logged in
					// and then set the param
					if (isset($this->current_user->id) and is_numeric($this->current_user->id))
					{
						$restrict_user = $this->current_user->id;
					}
				}
				elseif (is_numeric($restrict_user))
				{
					// It's numeric, meaning we don't have to do anything. Durrr...
				}
				else
				{
					// Looks like they might have put in a user's handle
					$user = $this->db
							->select('id')
							->limit(1)
							->where('username', $user)
							->get('users');
					
					$restrict_user = ($user) ? $user->id : 'no';
				}
			}
		
			if ($restrict_user != 'no' and is_numeric($restrict_user))
			{
				$this->sql['where'][] = $this->select_prefix.$this->db->protect_identifiers('created_by').'='.$restrict_user;
			}
		}

		// -------------------------------------
		// Get by ID
		// -------------------------------------
		
		if (isset($id) and is_numeric($id))
		{
			$this->sql['where'][] = $this->select_prefix.$this->db->protect_identifiers('id').'='.$id;
			$limit = 1;
		}

		// -------------------------------------
		// Single
		// -------------------------------------
		// I don't even know why this exists
		// really, but it does make sure that
		// limit is set to one.
		// -------------------------------------

		if (isset($single) and $single == 'yes')
		{
			$limit = 1;
		}

		// -------------------------------------
		// Hook
		// -------------------------------------
		// This hook can be used by fields
		// to add to the query
		// -------------------------------------
		
		if ($this->get_rows_hook)
		{
			if (method_exists($this->get_rows_hook[0], $this->get_rows_hook[1]))
			{
				$this->get_rows_hook[0]->{$this->get_rows_hook[1]}($this->get_rows_hook_data);
			}
		}
		
		// -------------------------------------
		// Run Our Select
		// -------------------------------------

		$sql = $this->build_query($this->sql);
		
		// -------------------------------------
		// Pagination
		// -------------------------------------
		
		if (isset($paginate) and $paginate == 'yes')
		{
			// Run the query as is. It does not
			// have limit/offset, so we can get the
			// total num rows with the current
			// parameters we have applie.
			$return['pag_count'] = $this->db->query($sql)->num_rows();
			
			// Set the offset. Blank segment
			// is a 0 offset.
			$offset = $this->uri->segment($pag_segment, 0);
		}

		// -------------------------------------
		// Offset 
		// -------------------------------------
		// Just in case.
		// -------------------------------------

		if ( ! isset($offset))
		{
			$offset = 0;
		}

		// -------------------------------------
		// Limit & Offset
		// -------------------------------------
		
		if (isset($limit) and is_numeric($limit))
		{
			$sql .= ' LIMIT '.$limit;
		}

		if (isset($offset) and is_numeric($offset) and $offset != 0)
		{
			$sql .= ' OFFSET '.$offset;
		}

		// -------------------------------------
		// Run the Get
		// -------------------------------------

		$rows = $this->db->query($sql)->result_array();

		// -------------------------------------
		// Partials
		// -------------------------------------
		// Paritals are done after the data grab
		// so we can still partials outsider of
		// limits/offsets in queries.
		// -------------------------------------
				
		if (isset($partial) and ! is_null($partial))
		{
			if (count($partials = explode('of', $partial)) == 2 and is_numeric($partials[1]))
			{
				// Break the array into how many pieces
				// we want.
				$chunks = array_chunk($rows, ceil(count($rows)/$partials[1]), true);
								
				if (isset($chunks[$partials[0]-1]))
				{
					$rows =& $chunks[$partials[0]-1];
				}
			}
		}
		
		// -------------------------------------
		// Run formatting
		// -------------------------------------
				
		$return['rows'] = $this->format_rows($rows, $stream, $disable);
		
		// Reset
		$this->get_rows_hook = array();
		$this->sql = array();
		$this->db->set_dbprefix(SITE_REF.'_');
				
		return $return;
	}


	// --------------------------------------------------------------------------

	/**
	 * Build Query
	 *
	 * Does not do LIMIT/OFFSET since that will
	 * be taken care of after pagination is 
	 * calculated.
	 */
	public function build_query($sql)
	{
		// -------------------------------------
		// Select
		// -------------------------------------

		if (is_string($this->sql['select']))
		{
			$select = $this->sql['select'];
		}
		else
		{
			$select = implode(', ', $this->sql['select']);
		}
		
		// -------------------------------------
		// From
		// -------------------------------------

		if (is_string($this->sql['from']))
		{
			$from = $this->sql['from'];
		}
		else
		{
			$from = implode(', ', $this->sql['from']);
		}

		// -------------------------------------
		// Where
		// -------------------------------------

		if (is_string($this->sql['where']))
		{
			$where = $this->sql['where'];
		}
		else
		{
			$where = implode(' AND ', $this->sql['where']);
		}

		if ($where != '')
		{
			$where = 'WHERE '.$where;
		}

		// -------------------------------------
		// Order By
		// -------------------------------------
		// If there is a RAND, make sure it
		// is the only order by segment
		// -------------------------------------

		if (is_string($this->sql['order_by']))
		{
			$order_by = $this->sql['order_by'];
		}
		else
		{
			$order_by = implode(', ', $this->sql['order_by']);
		}

		if ($order_by)
		{
			$order_by = 'ORDER BY '.$order_by;
		}

		// -------------------------------------
		// Misc
		// -------------------------------------

		if (is_string($this->sql['misc']))
		{
			$misc = $this->sql['misc'];
		}
		else
		{
			$misc = implode(' ', $this->sql['misc']);
		}

		// -------------------------------------
		// Build Query
		// -------------------------------------

		return "SELECT {$select}
		FROM {$from}
		{$where}
		{$misc}
		{$order_by} ";
	}

	// --------------------------------------------------------------------------
	
	/**
	 * Format Rows
	 *
	 * Takes raw array of data
	 * from the DB and formats it. Adds
	 * things like count and odd/even.
	 *
	 * @access	public
	 * @param	array - rows from db
	 * @param	obj - stream
	 * @param	[array - disables]
	 * @return	array
	 */
	public function format_rows($data, $stream, $disable = array())
	{
		$count = 1;

		$stream_fields = $this->streams_m->get_stream_fields($stream->id);
		
		$total = count($data);
		
		foreach ($data as $id => $item)
		{
			// Log the ID called
			$this->called[$stream->stream_slug][] = $item['id'];
		
			$data[$id] = $this->format_row($item, $stream_fields, $stream, false, true, $disable);
			
			// Give some info on if it is the last element
			$data[$id]['last'] = ($count == $total) ? '1' : '0';
						
			// Odd/Even			
			$data[$id]['odd_even'] = ($count%2 == 0) ? 'even' : 'odd';
			
			// Count
			$data[$id]['count'] = $count;
			
			$count++;
		}
		
		return $data;
	}

	// --------------------------------------------------------------------------
	
	/**
	 * Get a row
	 *
	 * @access	public
	 * @param	int
	 * @param	obj
	 * @param	[bool]
	 * @return	mixed
	 */
	public function get_row($id, $stream, $format_output = true)
	{
		// First, let's get all out fields. That's
		// right. All of them.
		if ( ! $this->all_fields)
		{
			$this->all_fields = $this->fields_m->get_all_fields();
		}
		
		// Now the structure. We will need this as well.
		if ( ! $this->structure)
		{
			$this->structure = $this->gather_structure();
		}

		$stream_fields = $this->streams_m->get_stream_fields($stream->id);

		$obj = $this->db->limit(1)->where('id', $id)->get($stream->stream_prefix.$stream->stream_slug);
		
		if ($obj->num_rows() == 0)
		{
			return false;
		}
		else
		{
			$row = $obj->row();
			
			if ($format_output)
			{
				return $this->format_row($row , $stream_fields, $stream);
			}
			else
			{	
				return $row;
			}
		}
	}

	// --------------------------------------------------------------------------	
	
	/**
	 * Format Row
	 *
	 * Formats a row based on format profile
	 *
	 * @access	public
	 * @param	array or obj
	 * @param	array
	 * @param	obj
	 * @param	[bool]
	 * @param	[bool]
	 * @param	[array - things to disable]
	 */
	public function format_row($row, $stream_fields, $stream, $return_object = true, $plugin_call = false,  $disable = array())
	{		
		// First, let's get all out fields. That's
		// right. All of them.
		if ( ! $this->all_fields)
		{
			$this->all_fields = $this->fields_m->get_all_fields();
		}

		// Now the structure. We will need this as well.
		if ( ! $this->structure)
		{
			$this->structure = $this->gather_structure();
		}

		// -------------------------------------
		// Format Rows
		// -------------------------------------
		// Go through each row and 
		// get the data from the plugin or
		// format it
		// -------------------------------------

		foreach ($row as $row_slug => $data)
		{
			// Easy out for our non-formattables and
			// fields we are disabling.
			if (in_array($row_slug, array('id')) or in_array($row_slug, $disable))
			{
				continue;
			}
			
			// -------------------------------------
			// Format Created By
			// -------------------------------------
			
			if(
				$row_slug == 'created_by' and 
				isset($this->type->types->user) and 
				method_exists($this->type->types->user, 'pre_output_plugin')
			)
			{
				if ($return_object)
				{
					$row->created_by	= $this->type->types->user->pre_output_plugin($row->created_by, null);
				}
				else
				{	
					$row['created_by']	= $this->type->types->user->pre_output_plugin($row['created_by'], null);
				}
			}
			
			// -------------------------------------
			// Format Dates
			// -------------------------------------
			
			if ($row_slug == 'created' or $row_slug == 'updated')
			{
				if ($return_object)
				{
					$row->$row_slug = strtotime($row->$row_slug);
				}
				else
				{
					$row[$row_slug] = strtotime($row[$row_slug]);
				}
			}	

			// -------------------------------------
			// Format Columns
			// -------------------------------------

			if (array_key_exists($row_slug, $this->all_fields))
			{
				$format_data = $this->all_fields[$row_slug];
						
				$type = $this->type->types->{$format_data['field_type']};
							
				// First off, is this an alt process type?
				if (isset($type->alt_process) and $type->alt_process === true)
				{
					$out = null;
				
					if ( ! $plugin_call and method_exists($type, 'alt_pre_output'))
					{
						$out = $type->alt_pre_output($row->id, $format_data['field_data'], $type, $stream);
					}
					
					($return_object) ? $row->$row_slug = $out : $row[$row_slug] = $out;
				}	
				else
				{
					// If not, check and see if there is a method
					// for pre output or pre_output_plugin
					if ($plugin_call and method_exists($type, 'pre_output_plugin'))
					{
						$plugin_output = $type->pre_output_plugin($row[$row_slug], $format_data['field_data'], $row_slug);
					
						// Do we get an array or a string?
						if (is_array($plugin_output))
						{
							// For arrays we replace the node with a new array
							// of data merged into the current array
							if (is_array($row))
							{
								if (isset($type->plugin_return) and $type->plugin_return == 'array')
								{
									$row[$row_slug] = $plugin_output;
								}
								elseif (isset($type->plugin_return) and $type->plugin_return == 'cycle')
								{
									// Don't do shit
								}
								else
								{
									($return_object) ? $row->{$row_slug} = $plugin_output : $row[$row_slug] = $plugin_output;
								}
							}
						}
						else
						{
							// Else it was just a special plugin output and we just need 
							// to use that string output.
							$row[$row_slug] = $plugin_output;
						}
					}
					elseif (method_exists($type, 'pre_output'))
					{
						if (is_array($row))
						{
							$out = $type->pre_output($row[$row_slug], $format_data['field_data']);
						}
						else
						{
							$out = $type->pre_output($row->$row_slug, $format_data['field_data']);
						}
						
						($return_object) ? $row->{$row_slug} = $out : $row[$row_slug] = $out;
					}
				}				
			}
		}		

		// -------------------------------------
		// Run through alt processes
		// -------------------------------------
		// If this is not a plugin call, we just
		// need to get the alt processes and
		// add them to the row for display
		// -------------------------------------
		
		if ( ! $plugin_call)
		{
			if ($stream_fields)
			{
				foreach ($stream_fields as $row_slug => $f)
				{
					if (isset($f->field_type, $this->ignore))
					{
						if(
							isset($this->type->types->{$f->field_type}->alt_process) and 
							$this->type->types->{$f->field_type}->alt_process === true and 
							method_exists($this->type->types->{$f->field_type}, 'alt_pre_output')
						)
						{
							$out = $this->type->types->{$f->field_type}->alt_pre_output($row->id, $this->all_fields[$row_slug]['field_data'], $f->field_type, $stream);
								
							($return_object) ? $row->$row_slug = $out : $row[$row_slug] = $out;
						}
					}
				}
			}
		}
		
		return $row;			
	}

	// --------------------------------------------------------------------------	
	
	/**
	 * Gather Structure
	 *
	 * Get the structure of the streams down. We never know
	 * when we are going to need this for formatting or
	 * reference.
	 *
	 * @access	public
	 * @return	array
	 */
	public function gather_structure()
	{		
		$obj = $this->db->query('
			SELECT '.PYROSTREAMS_DB_PRE.STREAMS_TABLE.'.*, '.PYROSTREAMS_DB_PRE.STREAMS_TABLE.'.id as stream_id, '.PYROSTREAMS_DB_PRE.FIELDS_TABLE.'.* 
			FROM '.PYROSTREAMS_DB_PRE.STREAMS_TABLE.', '.PYROSTREAMS_DB_PRE.ASSIGN_TABLE.', '.PYROSTREAMS_DB_PRE.FIELDS_TABLE.'
			WHERE '.PYROSTREAMS_DB_PRE.STREAMS_TABLE.'.id='.PYROSTREAMS_DB_PRE.ASSIGN_TABLE.'.stream_id and
			'.PYROSTREAMS_DB_PRE.FIELDS_TABLE.'.id='.PYROSTREAMS_DB_PRE.ASSIGN_TABLE.'.field_id');

		$fields = $obj->result();
		
		$struct = array();
		
		foreach ($this->streams_m->streams_cache as $stream_id => $stream)
		{
			$struct[$stream_id]['stream'] = $stream;
			
			foreach ($fields as $field)
			{
				if ($field->stream_slug == $stream->stream_slug)
				{
					$struct[$stream_id]['fields'][] = $field;
				}
			}
		}
		
		return $struct;
	}
	
	// --------------------------------------------------------------------------	
	
	/**
	 * Update a row in a stream
	 *
	 * @access	public
	 * @param	obj
	 * @param 	string
	 * @param	int
	 * @param	array - update data
	 * @param	skips - optional array of skips
	 * @return	bool
	 */
	public function update_entry($fields, $stream, $row_id, $data, $skips = array())
	{
		// -------------------------------------
		// Run through fields
		// -------------------------------------

		$update_data = array();
		
		foreach ($fields as $field)
		{
			if ( ! in_array($field->field_slug, $skips))
			{
				$type_call = $field->field_type;
			
				$type = $this->type->types->$type_call;
	
				if ( ! isset($type->alt_process) or ! $type->alt_process)
				{
					// If a pre_save function exists, go ahead and run it
					if (method_exists($type, 'pre_save'))
					{
						// Special case for data this is not there.
						if ( ! isset($data[$field->field_slug]))
						{
							$data[$field->field_slug] = null;
						}
					
						$update_data[$field->field_slug] = $type->pre_save(
									$data[$field->field_slug],
									$field,
									$stream,
									$row_id,
									$data
						);
					}
					else
					{
						$update_data[$field->field_slug] = $data[$field->field_slug];
	
						// Make null - some fields don't like just blank values
						if ($update_data[$field->field_slug] == '')
						{
							$update_data[$field->field_slug] = null;
						}
					}
				}	
				else
				{
					// If this is an alt_process, there can still be a pre_save,
					// it just won't return anything so we don't have to
					// save the value
					if (method_exists($type, 'pre_save'))
					{
						$type->pre_save(
									$data[$field->field_slug],
									$field,
									$stream,
									$row_id,
									$data
						);
					}
				}
			}	
		}

		// -------------------------------------
		// Set standard fields
		// -------------------------------------

		$update_data['updated'] = date('Y-m-d H:i:s');
		
		// -------------------------------------
		// Insert data
		// -------------------------------------
		
		$this->db->where('id', $row_id);
		
		if ( ! $this->db->update($stream->stream_prefix.$stream->stream_slug, $update_data))
		{
			return false;
		}
		else
		{
			return $row_id;
		}
	}

	// --------------------------------------------------------------------------
	
	/**
	 * Insert field to a stream
	 *
	 * @access	public
	 * @param	array - data
	 * @param	obj - our stream fields
	 * @param	obj - our stream
	 * @param	array - optional skipping fields
	 * @return	mixed
	 */
	public function insert_entry($data, $fields, $stream, $skips = array())
	{
		// -------------------------------------
		// Run through fields
		// -------------------------------------

		$insert_data = array();
		
		$alt_process = array();
			
		foreach ($fields as $field)
		{
			if ( ! in_array($field->field_slug, $skips) or (in_array($field->field_slug, $skips) and isset($_POST[$field->field_slug])))
			{
				$type = $this->type->types->{$field->field_type};
				
				if (isset($data[$field->field_slug]) and $data[$field->field_slug] != '')
				{
					// We don't process the alt process stuff.
					// This is for field types that store data outside of the
					// actual table
					if (isset($type->alt_process) and $type->alt_process === true)
					{
						$alt_process[] = $field->field_slug;
					}
					else
					{
						if (method_exists($type, 'pre_save'))
						{
							$data[$field->field_slug] = $type->pre_save($data[$field->field_slug], $field, $stream, null, $data);
						}
						
						// Trim if a string
						if (is_string($data[$field->field_slug]))
						{
							$data[$field->field_slug] = trim($data[$field->field_slug]);
						}
						
						$insert_data[$field->field_slug] = $data[$field->field_slug];

						// Make null - some fields don't like just blank values
						if ($insert_data[$field->field_slug] == '')
						{
							$insert_data[$field->field_slug] = null;
						}
					}
				}
				
				unset($type);
			}
		}

		// -------------------------------------
		// Set standard fields
		// -------------------------------------

		$insert_data['created'] 	= date('Y-m-d H:i:s');
		$insert_data['created_by'] 	= $this->current_user->id;

		// -------------------------------------
		// Set incremental ordering
		// -------------------------------------
		
		$db_obj = $this->db->select("MAX(ordering_count) as max_ordering")->get($stream->stream_prefix.$stream->stream_slug);
		
		if ($db_obj->num_rows() == 0 or !$db_obj)
		{
			$ordering = 0;
		}
		else
		{
			$order_row = $db_obj->row();
			
			if ( ! is_numeric($order_row->max_ordering))
			{
				$ordering = 0;
			}
			else
			{
				$ordering = $order_row->max_ordering;
			}
		}

		$insert_data['ordering_count'] 	= $ordering+1;

		// -------------------------------------
		// Insert data
		// -------------------------------------
		
		if ( ! $this->db->insert($stream->stream_prefix.$stream->stream_slug, $insert_data))
		{
			return false;
		}
		else
		{
			$id = $this->db->insert_id();
			
			// Process any alt process stuff
			foreach ($alt_process as $field_slug)
			{
				$this->type->types->{$fields->$field_slug->field_type}->pre_save($data[$field_slug], $fields->{$field_slug}, $stream, $id, $data);
			}
			
			return $id;
		}
	}

	// --------------------------------------------------------------------------
	
	/**
	 * Build Row Pagination
	 *
	 * @access	public
	 * @param	int - pagination uri segment
	 * @param	int - limit
	 * @param	int - total rows
	 * @Param	array - pagination configs
	 * @return	string
	 */
	public function build_pagination($pag_segment, $limit, $total_rows, $pagination_vars)
	{
		$this->load->library('pagination');
	
		// -------------------------------------
		// Find Pagination base_url
		// -------------------------------------

		$segments = $this->uri->segment_array();
		
		if (isset($segments[count($segments)]) and is_numeric($segments[count($segments)]))
		{
			unset($segments[count($segments)]);
		}
		
		$pag_uri = '';
		
		foreach ($segments as $segment)
		{
			$pag_uri .= $segment.'/';
		}
		
		$pagination_config['base_url'] 			= site_url( $pag_uri );
		
		// -------------------------------------
		// Set basic pagination data
		// -------------------------------------

		$pagination_config['total_rows'] 		= $total_rows;
		$pagination_config['per_page'] 			= $limit;
		$pagination_config['uri_segment'] 		= $pag_segment;
		
		// Add in our pagination vars
		$pagination_config = array_merge($pagination_config, $pagination_vars);

		// -------------------------------------
		// Build and return pagination
		// -------------------------------------
										
		$this->pagination->initialize($pagination_config);
		
		return $this->pagination->create_links();
	
	}

	// --------------------------------------------------------------------------	
	
	/**
	 * Delete a row
	 *
	 * @access	public
	 * @param	int
	 * @param	obj
	 * @return 	bool
	 */
	public function delete_row($row_id, $stream)
	{
		// Get the row
		$db_obj = $this->db->limit(1)->where('id', $row_id)->get($stream->stream_prefix.$stream->stream_slug);
		
		if ($db_obj->num_rows() == 0)
		{
			return false;
		}
		
		// Get the ordering count
		$row = $db_obj->row();
		$ordering_count = $row->ordering_count;
		
		// Delete the actual row
		$this->db->where('id', $row_id);
		
		if ( ! $this->db->delete($stream->stream_prefix.$stream->stream_slug))
		{
			return false;
		}
		else
		{
			// -------------------------------------
			// Entry Destructs
			// -------------------------------------
			// Go through the assignments and call
			// entry destruct methods
			// -------------------------------------
		
			// Get the assignments
			$assignments = $this->fields_m->get_assignments_for_stream($stream->id);
			
			// Do they have a destruct function?
			foreach ($assignments as $assign)
			{
				if (method_exists($this->type->types->{$assign->field_type}, 'entry_destruct'))
				{
					// Get the field
					$field = $this->fields_m->get_field($assign->field_id);
					$this->type->types->{$assign->field_type}->entry_destruct($row, $field, $stream);
				}
			}
		
			// -------------------------------------
			// Reset reordering
			// -------------------------------------
			// We're doing this by subtracting one to
			// everthing higher than the row's
			// order count
			// -------------------------------------
			
			$this->db->where('ordering_count >', $ordering_count)->select('id, ordering_count');
			$ord_obj = $this->db->get($stream->stream_prefix.$stream->stream_slug);
			
			if ($ord_obj->num_rows() > 0)
			{
				$rows = $ord_obj->result();
				
				foreach ($rows as $update_row)
				{
					$update_data['ordering_count'] = $update_row->ordering_count-1;
					
					$this->db->where('id', $update_row->id);
					$this->db->update($stream->stream_prefix.$stream->stream_slug, $update_data);
					
					$update_data = array();
				}
			}
			
			return true;
		}
	}

}