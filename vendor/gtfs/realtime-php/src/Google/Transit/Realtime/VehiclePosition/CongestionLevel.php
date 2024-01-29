<?php
# Generated by the protocol buffer compiler.  DO NOT EDIT!
# source: gtfs-realtime.proto

namespace Google\Transit\Realtime\VehiclePosition;

use UnexpectedValueException;

/**
 * Congestion level that is affecting this vehicle.
 *
 * Protobuf type <code>Google.Transit.Realtime.VehiclePosition.CongestionLevel</code>
 */
class CongestionLevel
{
    /**
     * Generated from protobuf enum <code>UNKNOWN_CONGESTION_LEVEL = 0;</code>
     */
    const UNKNOWN_CONGESTION_LEVEL = 0;
    /**
     * Generated from protobuf enum <code>RUNNING_SMOOTHLY = 1;</code>
     */
    const RUNNING_SMOOTHLY = 1;
    /**
     * Generated from protobuf enum <code>STOP_AND_GO = 2;</code>
     */
    const STOP_AND_GO = 2;
    /**
     * Generated from protobuf enum <code>CONGESTION = 3;</code>
     */
    const CONGESTION = 3;
    /**
     * People leaving their cars.
     *
     * Generated from protobuf enum <code>SEVERE_CONGESTION = 4;</code>
     */
    const SEVERE_CONGESTION = 4;

    private static $valueToName = [
        self::UNKNOWN_CONGESTION_LEVEL => 'UNKNOWN_CONGESTION_LEVEL',
        self::RUNNING_SMOOTHLY => 'RUNNING_SMOOTHLY',
        self::STOP_AND_GO => 'STOP_AND_GO',
        self::CONGESTION => 'CONGESTION',
        self::SEVERE_CONGESTION => 'SEVERE_CONGESTION',
    ];

    public static function name($value)
    {
        if (!isset(self::$valueToName[$value])) {
            throw new UnexpectedValueException(sprintf(
                    'Enum %s has no name defined for value %s', __CLASS__, $value));
        }
        return self::$valueToName[$value];
    }


    public static function value($name)
    {
        $const = __CLASS__ . '::' . strtoupper($name);
        if (!defined($const)) {
            throw new UnexpectedValueException(sprintf(
                    'Enum %s has no value defined for name %s', __CLASS__, $name));
        }
        return constant($const);
    }
}

// Adding a class alias for backwards compatibility with the previous class name.
class_alias(CongestionLevel::class, \Google\Transit\Realtime\VehiclePosition_CongestionLevel::class);

