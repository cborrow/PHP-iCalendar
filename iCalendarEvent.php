<?php
/**
 * iCalendarEvent class
 *
 * Creates and stores information for an iCalendar event.
 *
 * @package iCalendar
 * @author Cory Borrow
 * @copyright 2015 Cory Borrow
 * @version 1.0
 */
class iCalendarEvent {
	/**
	 * The summary of the event
	 * @access public
	 * @var string
	 */
	public $Summary;

	/**
	 * The description of the event.
	 * @access public
	 * @var string
	 */
	public $Description;

	/**
	 * The location of the event (e.g. 123 Spooner st, Perfection, NW, USA)
	 * @access public
	 * @var string
	 */
	public $Location;

	/**
	 * The event organizer, stored as a keyed array.
	 * @access protected
	 * @var array
	 */
	protected $organizer;

	/**
	 * The attentees stored as an array of keyed array's.
	 * @access protected
	 * @var array
	 */
	protected $attendees;

	/**
	 * The time the event was created. Automatically generated.
	 * @access protected
	 * @var int
	 */
	protected $created;

	/**
	 * The datetime stamp of the event. Uses dtStart
	 * @access protected
	 * @var int
	 */
	protected $dtStamp;

	/**
	 * The datetime start of an event in UNIX timestamp
	 * @access protected
	 * @var int
	 */
	protected $dtStart;

	/**
	 * The datetime end of an event in UNIX timestamp
	 * @access protected
	 * @var int
	 */
	protected $dtEnd;

	/**
	 * An array of alarms, each of which are in a keyed array.
	 * @access protected
	 * @var array
	 */
	protected $alarms;

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->Description = "";
		$this->Summary = "New Calendar Event";

		$this->organizer = "";
		$this->attendees = array();

		$this->created = time();
		$this->dtStamp = date("Ymd\This\Z", time());
		$this->dtStart = "";
		$this->dtEnd = "";

		$this->alarms = array();
	}

	/**
	 * Sets the organizer for the event. Automatically adds organizer to attendee's as well.
	 *
	 * @access public
	 * @param string $email The organizers email address.
	 * @param string $name The organizers name [optional]
	 */
	public function setOrganizer($email, $name = null) {
		if($name == null)
			$name = $email;

		//$this->organizer = "ORGANIZER;CN={$name}:mailto:{$email};";
		$this->organizer = ['name' => $name, 'email' => $email, 'status' => 'ACCEPTED'];
	}

	/**
	 * Adds an attendee to the event.
	 *
	 * @access public
	 * @param string $email The email address of an attendee
	 * @param string $name The name of an attendee [optional]
	 */
	public function addAttendee($email, $name = null) {
		if($name == null)
			$name = $email;

		$this->attendees[] = ['name' => $name, 'email' => $email, 'status' => 'NEEDS-ACTION'];
	}

	/**
	 * Sets the start time for an event.
	 *
	 * @access public
	 * @param int $time The time in UNIX timestamp or formatted date.
	 */
	public function setStartTime($time) {
		if(is_numeric($time))
			$this->dtStart = date("Ymd\THis\Z", $time + +abs(date("Z", time())));
		else
			$this->dtStart = $time;
	}

	/**
	 * Sets the end time for an event.
	 *
	 * @access public
	 * @param int $time The time in UNIX timestamp or formatted date.
	 */
	public function setEndTime($time) {
		if(is_numeric($time))
			$this->dtEnd = date("Ymd\THis\Z", $time + abs(date("Z", time())));
		else
			$this->dtEnd = $time;
	}

	/**
	 * Adds a new alarm / event reminder.
	 *
	 * @access public
	 * @param int $seconds The time before the event to show alarm.
	 * @param string $title A description / title for the alarm. [optional]
	 */
	public function addAlarm($seconds, $title = 'This is an event reminder') {
		$this->alarms[] = ['time' => $seconds, 'title' => $title];
	}

	/**
	 * Returns the event with alarms in the iCalendar format.
	 *
	 * @return string iCalendar event with alarms.
	 */
	public function toString() {
		if($this->organizer == null)
			throw new Exception("An organizer is required.");
		if(count($this->attendees) == 0)
			throw new Exception("At least one attendee is required.");

		$output = "BEGIN:VEVENT\r\n";
		$output .= "DTSTART:{$this->dtStart}\r\n";
		$output .= "DTEND:{$this->dtEnd}\r\n";
		$output .= "DTSTAMP:{$this->dtStamp}\r\n";
		//$output .= $this->organizer . "\r\n";
		$output .= "ORGANIZER;CN={$this->organizer['name']}:mailto:{$this->organizer['email']}\r\n";
		$output .= "UID:" . $this->GUID() . "\r\n";

		$this->attendees[] = $this->organizer;
		foreach($this->attendees as $a) {
			$temp = "ATTENDEE;CUTYPE=INDIVIDUAL;ROLE=REQ-PARTICIPANT;PARTSTAT={$a['status']};RSVP=TRUE;CN={$a['name']};X-NUM-GUESTS=0:mailto:{$a['email']}\r\n";

			if(strlen($temp) > 75)
				$temp = substr($temp, 0, 75) . "\r\n " . substr($temp, 75);
			$output .= $temp;
		}

		$output .= "CREATED:" . date("Ymd\THis\Z", time() + abs(date("Z", time()))) . "\r\n";

		$temp = $this->Description;
		$textSpan = strlen($this->Description);
		$parts = array();

		while(strlen($temp) > 75) {
			$parts[] = substr($temp, 0, 75);
			$temp = substr($temp, 75);
		}

		$output .= "DESCRIPTION:";
		if(count($parts) > 0)
			$output .= implode("\r\n", $parts);
		else
			$output .= $temp . "\r\n";
		$output .= "LAST-MODIFIED:" . $this->dtStamp;
		$output .= "LOCATION:{$this->Location}\r\n";
		$output .= "SEQUENCE:0\r\n";
		$output .= "STATUS:CONFIRMED\r\n";
		$output .= "SUMMARY:{$this->Summary}\r\n";
		$output .= "TRANSP:OPAQUE\r\n";

		foreach($this->alarms as $al) {
			$output .= "BEGIN:VALARM\r\n";
			$output .= "ACTION:DISPLAY\r\n";
			$output .= "DESCRIPTION:{$al['title']}\r\n";
			$output .= "TRIGGER:-P" . $this->toTriggerTime($al['time']) . "\r\n";
			$output .= "END:VALARM\r\n";
		}

		$output .= "END:VEVENT\r\n";
		return $output;
	}

	//From the phunction PHP framework
	/**
	 * Returns a Version 4 GUID.
	 *
	 * @access  private
	 * @return string Returns a Version 4 GUID.
	 */
	private function GUID() {
	    if (function_exists('com_create_guid')) {
	        return trim(com_create_guid(), '{}');
	    }

    	return sprintf('%04X%04X-%04X-%04X-%04X-%04X%04X%04X', mt_rand(0, 65535), mt_rand(0, 65535), mt_rand(0, 65535), mt_rand(16384, 20479), mt_rand(32768, 49151), mt_rand(0, 65535), mt_rand(0, 65535), mt_rand(0, 65535));
	}

	/**
	 * Returns a formatted trigger time for iCalendar VALARM:TRIGGER.
	 *
	 * @access private
	 * @param  int $time The time to trigger before event in seconds (e.g. 3600 = 1 Hour)
	 * @return string The formatted time
	 */
	private function toTriggerTime($time) {
		$out = "";
		$days = 0;
		$hours = 0;
		$minutes = 0;
		$seconds = 0;

		if($time >= 86400) {
			$days = floor($time / 86400);
			$time = $time - (86400 * $days);
		}
		if($time >= 3600) {
			$hours = floor($time / 3600);
			$time = $time - (3600 * $hours);
		}
		if($time >= 60) {
			$minutes = floor($time / 60);
			$time = $time - (60 * $minutes);
		}
		$seconds = ceil($time);

		return "{$days}DT{$hours}H{$minutes}M{$seconds}S";
	}
}
?>