<?php
# Generated by the protocol buffer compiler.  DO NOT EDIT!
# source: gtfs-realtime.proto

namespace Google\Transit\Realtime\TripUpdate;

use Google\Protobuf\Internal\GPBType;
use Google\Protobuf\Internal\RepeatedField;
use Google\Protobuf\Internal\GPBUtil;

/**
 * Realtime update for arrival and/or departure events for a given stop on a
 * trip. Updates can be supplied for both past and future events.
 * The producer is allowed, although not required, to drop past events.
 *
 * Generated from protobuf message <code>Google.Transit.Realtime.TripUpdate.StopTimeUpdate</code>
 */
class StopTimeUpdate extends \Google\Protobuf\Internal\Message
{
    /**
     * Must be the same as in stop_times.txt in the corresponding GTFS feed.
     *
     * Generated from protobuf field <code>uint32 stop_sequence = 1;</code>
     */
    protected $stop_sequence = 0;
    /**
     * Must be the same as in stops.txt in the corresponding GTFS feed.
     *
     * Generated from protobuf field <code>string stop_id = 4;</code>
     */
    protected $stop_id = '';
    /**
     * Generated from protobuf field <code>.Google.Transit.Realtime.TripUpdate.StopTimeEvent arrival = 2;</code>
     */
    protected $arrival = null;
    /**
     * Generated from protobuf field <code>.Google.Transit.Realtime.TripUpdate.StopTimeEvent departure = 3;</code>
     */
    protected $departure = null;
    /**
     * Expected occupancy after departure from the given stop.
     * Should be provided only for future stops.
     * In order to provide departure_occupancy_status without either arrival or
     * departure StopTimeEvents, ScheduleRelationship should be set to NO_DATA. 
     *
     * Generated from protobuf field <code>.Google.Transit.Realtime.VehiclePosition.OccupancyStatus departure_occupancy_status = 7;</code>
     */
    protected $departure_occupancy_status = 0;
    /**
     * ScheduleRelationship schedule_relationship = 5
     *     [default = SCHEDULED];
     *
     * Generated from protobuf field <code>.Google.Transit.Realtime.TripUpdate.StopTimeUpdate.ScheduleRelationship schedule_relationship = 5;</code>
     */
    protected $schedule_relationship = 0;
    /**
     * Realtime updates for certain properties defined within GTFS stop_times.txt
     * NOTE: This field is still experimental, and subject to change. It may be formally adopted in the future.
     *
     * Generated from protobuf field <code>.Google.Transit.Realtime.TripUpdate.StopTimeUpdate.StopTimeProperties stop_time_properties = 6;</code>
     */
    protected $stop_time_properties = null;

    /**
     * Constructor.
     *
     * @param array $data {
     *     Optional. Data for populating the Message object.
     *
     *     @type int $stop_sequence
     *           Must be the same as in stop_times.txt in the corresponding GTFS feed.
     *     @type string $stop_id
     *           Must be the same as in stops.txt in the corresponding GTFS feed.
     *     @type \Google\Transit\Realtime\TripUpdate\StopTimeEvent $arrival
     *     @type \Google\Transit\Realtime\TripUpdate\StopTimeEvent $departure
     *     @type int $departure_occupancy_status
     *           Expected occupancy after departure from the given stop.
     *           Should be provided only for future stops.
     *           In order to provide departure_occupancy_status without either arrival or
     *           departure StopTimeEvents, ScheduleRelationship should be set to NO_DATA. 
     *     @type int $schedule_relationship
     *           ScheduleRelationship schedule_relationship = 5
     *               [default = SCHEDULED];
     *     @type \Google\Transit\Realtime\TripUpdate\StopTimeUpdate\StopTimeProperties $stop_time_properties
     *           Realtime updates for certain properties defined within GTFS stop_times.txt
     *           NOTE: This field is still experimental, and subject to change. It may be formally adopted in the future.
     * }
     */
    public function __construct($data = NULL) {
        \GPBMetadata\Src\GtfsRealtime::initOnce();
        parent::__construct($data);
    }

    /**
     * Must be the same as in stop_times.txt in the corresponding GTFS feed.
     *
     * Generated from protobuf field <code>uint32 stop_sequence = 1;</code>
     * @return int
     */
    public function getStopSequence()
    {
        return $this->stop_sequence;
    }

    /**
     * Must be the same as in stop_times.txt in the corresponding GTFS feed.
     *
     * Generated from protobuf field <code>uint32 stop_sequence = 1;</code>
     * @param int $var
     * @return $this
     */
    public function setStopSequence($var)
    {
        GPBUtil::checkUint32($var);
        $this->stop_sequence = $var;

        return $this;
    }

    /**
     * Must be the same as in stops.txt in the corresponding GTFS feed.
     *
     * Generated from protobuf field <code>string stop_id = 4;</code>
     * @return string
     */
    public function getStopId()
    {
        return $this->stop_id;
    }

    /**
     * Must be the same as in stops.txt in the corresponding GTFS feed.
     *
     * Generated from protobuf field <code>string stop_id = 4;</code>
     * @param string $var
     * @return $this
     */
    public function setStopId($var)
    {
        GPBUtil::checkString($var, True);
        $this->stop_id = $var;

        return $this;
    }

    /**
     * Generated from protobuf field <code>.Google.Transit.Realtime.TripUpdate.StopTimeEvent arrival = 2;</code>
     * @return \Google\Transit\Realtime\TripUpdate\StopTimeEvent
     */
    public function getArrival()
    {
        return $this->arrival;
    }

    /**
     * Generated from protobuf field <code>.Google.Transit.Realtime.TripUpdate.StopTimeEvent arrival = 2;</code>
     * @param \Google\Transit\Realtime\TripUpdate\StopTimeEvent $var
     * @return $this
     */
    public function setArrival($var)
    {
        GPBUtil::checkMessage($var, \Google\Transit\Realtime\TripUpdate_StopTimeEvent::class);
        $this->arrival = $var;

        return $this;
    }

    /**
     * Generated from protobuf field <code>.Google.Transit.Realtime.TripUpdate.StopTimeEvent departure = 3;</code>
     * @return \Google\Transit\Realtime\TripUpdate\StopTimeEvent
     */
    public function getDeparture()
    {
        return $this->departure;
    }

    /**
     * Generated from protobuf field <code>.Google.Transit.Realtime.TripUpdate.StopTimeEvent departure = 3;</code>
     * @param \Google\Transit\Realtime\TripUpdate\StopTimeEvent $var
     * @return $this
     */
    public function setDeparture($var)
    {
        GPBUtil::checkMessage($var, \Google\Transit\Realtime\TripUpdate_StopTimeEvent::class);
        $this->departure = $var;

        return $this;
    }

    /**
     * Expected occupancy after departure from the given stop.
     * Should be provided only for future stops.
     * In order to provide departure_occupancy_status without either arrival or
     * departure StopTimeEvents, ScheduleRelationship should be set to NO_DATA. 
     *
     * Generated from protobuf field <code>.Google.Transit.Realtime.VehiclePosition.OccupancyStatus departure_occupancy_status = 7;</code>
     * @return int
     */
    public function getDepartureOccupancyStatus()
    {
        return $this->departure_occupancy_status;
    }

    /**
     * Expected occupancy after departure from the given stop.
     * Should be provided only for future stops.
     * In order to provide departure_occupancy_status without either arrival or
     * departure StopTimeEvents, ScheduleRelationship should be set to NO_DATA. 
     *
     * Generated from protobuf field <code>.Google.Transit.Realtime.VehiclePosition.OccupancyStatus departure_occupancy_status = 7;</code>
     * @param int $var
     * @return $this
     */
    public function setDepartureOccupancyStatus($var)
    {
        GPBUtil::checkEnum($var, \Google\Transit\Realtime\VehiclePosition_OccupancyStatus::class);
        $this->departure_occupancy_status = $var;

        return $this;
    }

    /**
     * ScheduleRelationship schedule_relationship = 5
     *     [default = SCHEDULED];
     *
     * Generated from protobuf field <code>.Google.Transit.Realtime.TripUpdate.StopTimeUpdate.ScheduleRelationship schedule_relationship = 5;</code>
     * @return int
     */
    public function getScheduleRelationship()
    {
        return $this->schedule_relationship;
    }

    /**
     * ScheduleRelationship schedule_relationship = 5
     *     [default = SCHEDULED];
     *
     * Generated from protobuf field <code>.Google.Transit.Realtime.TripUpdate.StopTimeUpdate.ScheduleRelationship schedule_relationship = 5;</code>
     * @param int $var
     * @return $this
     */
    public function setScheduleRelationship($var)
    {
        GPBUtil::checkEnum($var, \Google\Transit\Realtime\TripUpdate_StopTimeUpdate_ScheduleRelationship::class);
        $this->schedule_relationship = $var;

        return $this;
    }

    /**
     * Realtime updates for certain properties defined within GTFS stop_times.txt
     * NOTE: This field is still experimental, and subject to change. It may be formally adopted in the future.
     *
     * Generated from protobuf field <code>.Google.Transit.Realtime.TripUpdate.StopTimeUpdate.StopTimeProperties stop_time_properties = 6;</code>
     * @return \Google\Transit\Realtime\TripUpdate\StopTimeUpdate\StopTimeProperties
     */
    public function getStopTimeProperties()
    {
        return $this->stop_time_properties;
    }

    /**
     * Realtime updates for certain properties defined within GTFS stop_times.txt
     * NOTE: This field is still experimental, and subject to change. It may be formally adopted in the future.
     *
     * Generated from protobuf field <code>.Google.Transit.Realtime.TripUpdate.StopTimeUpdate.StopTimeProperties stop_time_properties = 6;</code>
     * @param \Google\Transit\Realtime\TripUpdate\StopTimeUpdate\StopTimeProperties $var
     * @return $this
     */
    public function setStopTimeProperties($var)
    {
        GPBUtil::checkMessage($var, \Google\Transit\Realtime\TripUpdate_StopTimeUpdate_StopTimeProperties::class);
        $this->stop_time_properties = $var;

        return $this;
    }

}

// Adding a class alias for backwards compatibility with the previous class name.
class_alias(StopTimeUpdate::class, \Google\Transit\Realtime\TripUpdate_StopTimeUpdate::class);

