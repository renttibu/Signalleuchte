<?php

// Declare
declare(strict_types=1);

trait SIGL_signalLamp
{
    /**
     * Sets the system state signal lamp.
     *
     * @param int $State
     * 0    = disarmed
     * 1    = armed
     * 2    = delayed armed
     * 3    = other
     *
     * @param int $Color
     * 0    = off
     * 1    = blue
     * 2    = green
     * 3    = turquoise
     * 4    = red
     * 5    = violet
     * 6    = yellow
     * 7    = white
     *
     * @param int $Brightness
     *
     * @param int $AlarmState
     * 0    = no alarm
     * 1    = alarm
     * 2    = pre alarm
     * 3    = other
     */
    public function SetSystemStateSignalLamp(int $State, int $Color, int $Brightness, int $AlarmState): void
    {
        $signalLamps = json_decode($this->ReadPropertyString('SystemStateSignalLamps'));
        if (!empty($signalLamps)) {
            $devices = 0;
            foreach ($signalLamps as $signalLamp) {
                if ($signalLamp->Use) {
                    $devices++;
                }
            }
            if ($devices != 0) {
                $i = 0;
                foreach ($signalLamps as $signalLamp) {
                    if ($signalLamp->Use) {
                        $i++;
                        $id = $signalLamp->ID;
                        if ($id != 0 && IPS_ObjectExists($id)) {
                            $deviceType = $signalLamp->Type;
                            $brightness = $signalLamp->Brightness;
                            // Check if the device is also used as a door and window state signal lamp
                            $doorWindowStateSignalLamp = $this->CheckDoorWindowStateSignalLamp($id);
                            // Abort if the system is disarmed
                            if ($doorWindowStateSignalLamp && $State == 0) {
                                $this->SendDebug(__FUNCTION__, 'Abort, device is also used as a door and window state indicator', 0);
                                continue;
                            }
                            // Check if the device is also used as an alarm state signal lamp
                            $alarmStateSignalLamp = $this->CheckAlarmStateSignalLamp($id);
                            // Abort if and we have an alarm
                            if ($alarmStateSignalLamp) {
                                if ($AlarmState == 1) {
                                    $this->SendDebug(__FUNCTION__, 'Abort, device is also used as a an alarm state indicator', 0);
                                    continue;
                                }
                            }
                            switch ($State) {
                                // 0: Disarmed
                                case 0:
                                    switch ($signalLamp->Options) {
                                        // 1: Disarmed
                                        // 4: Disarmed, delayed armed
                                        // 6: Disarmed, armed
                                        // 7: All variants
                                        case 1:
                                        case 4:
                                        case 6:
                                        case 7:
                                            switch ($deviceType) {
                                                // Variable
                                                case 1:
                                                    @RequestAction($id, false);
                                                    break;

                                                // Script
                                                case 2:
                                                    @IPS_RunScriptEx($id, ['State' => 0]);
                                                    break;

                                                // HmIP
                                                case 3:
                                                case 4:
                                                case 5:
                                                    // Set color to green
                                                    $this->SetValue('SystemStateColor', 2);
                                                    $this->SetValue('SystemStateBrightness', $brightness);
                                                    $this->SetSignalLamp($id, 2, $brightness);
                                                    break;

                                            }
                                            break;

                                        // 2: Delayed armed
                                        // 3: Armed
                                        // 5: Delayed armed , armed
                                        case 2:
                                        case 3:
                                        case 5:
                                            switch ($deviceType) {
                                                // HmIP
                                                case 3:
                                                case 4:
                                                case 5:
                                                    // Set color to off
                                                    $this->SetValue('SystemStateColor', 0);
                                                    $this->SetValue('SystemStateBrightness', 0);
                                                    $this->SetSignalLamp($id, 0, 0);
                                                    break;

                                            }
                                            break;

                                    }
                                    break;

                                // 1: Armed
                                case 1:
                                    switch ($signalLamp->Options) {
                                        // 3: Armed
                                        // 5: Delay armed, armed
                                        // 6: Disarmed, armed
                                        // 7: All variants
                                        case 3:
                                        case 5:
                                        case 6:
                                        case 7:
                                            switch ($deviceType) {
                                                // Variable
                                                case 1:
                                                    @RequestAction($id, true);
                                                    break;

                                                // Script
                                                case 2:
                                                    IPS_RunScriptEx($id, ['State' => 1]);
                                                    break;

                                                // HmIP
                                                case 3:
                                                case 4:
                                                case 5:
                                                    // Set color to red
                                                    $this->SetValue('SystemStateColor', 4);
                                                    $this->SetValue('SystemStateBrightness', $brightness);
                                                    $this->SetSignalLamp($id, 4, $brightness);
                                                    break;

                                            }
                                            break;

                                        // 1: Disarmed
                                        // 4: Disarmed, delayed
                                        // 2: Delayed armed
                                        case 1:
                                        case 4:
                                        case 2:
                                            switch ($deviceType) {
                                                // HmIP
                                                case 3:
                                                case 4:
                                                case 5:
                                                    // Set color to off
                                                    $this->SetValue('SystemStateColor', 0);
                                                    $this->SetValue('SystemStateBrightness', 0);
                                                    $this->SetSignalLamp($id, 0, 0);
                                                    break;
                                            }
                                            break;

                                    }
                                    break;

                                // 2: Delayed Armed
                                case 2:
                                    switch ($signalLamp->Options) {
                                        // 2: Delayed armed
                                        // 4: Disarmed, delayed armed
                                        // 5: Delayed armed, armed
                                        // 7: All variants
                                        case 2:
                                        case 4:
                                        case 5:
                                        case 7:
                                            switch ($deviceType) {
                                                // Script
                                                case 2:
                                                    @IPS_RunScriptEx($id, ['State' => 2]);
                                                    break;

                                                // HmIP
                                                case 3:
                                                case 4:
                                                case 5:
                                                    // Set color to yellow
                                                    $this->SetValue('SystemStateColor', 6);
                                                    $this->SetValue('SystemStateBrightness', $brightness);
                                                    $this->SetSignalLamp($id, 6, $brightness);
                                                    break;

                                            }
                                            break;

                                        // 1: Disarmed
                                        // 3: Armed
                                        // 6: Disarmed, armed
                                        case 1:
                                        case 3:
                                        case 6:
                                            switch ($deviceType) {
                                                // HmIP
                                                case 3:
                                                case 4:
                                                case 5:
                                                    // Set color to off
                                                    $this->SetValue('SystemStateColor', 0);
                                                    $this->SetValue('SystemStateBrightness', 0);
                                                    $this->SetSignalLamp($id, 0, 0);
                                                    break;
                                            }
                                            break;

                                    }
                                    break;

                                // 3: Other
                                case 3:
                                    switch ($deviceType) {
                                        // HmIP
                                        case 3:
                                        case 4:
                                        case 5:
                                            $this->SetValue('SystemStateColor', $Color);
                                            $this->SetValue('SystemStateBrightness', $Brightness);
                                            $this->SetSignalLamp($id, $Color, $Brightness);
                                            break;

                                    }
                                    break;

                            }
                        }
                    }
                    // Execution delay for next device
                    if ($devices > 1 && $i < $devices) {
                        IPS_Sleep(self::DELAY_MILLISECONDS);
                    }
                }
            }
        }
    }

    /**
     * Sets the door window state signal lamp.
     *
     * @param int $State
     * 0    = closed
     * 1    = opened
     * 2    = other
     *
     * @param int $Color
     * 0    = off
     * 1    = blue
     * 2    = green
     * 3    = turquoise
     * 4    = red
     * 5    = violet
     * 6    = yellow
     * 7    = white
     *
     * @param int $Brightness
     *
     * @param int $SystemState
     * 0    = disarmed
     * 1    = armed
     * 2    = delayed armed
     * 3    = other
     *
     * @param int $AlarmState
     * 0    = no alarm
     * 1    = alarm
     * 2    = pre alarm
     * 3    = other
     */
    public function SetDoorWindowStateSignalLamp(int $State, int $Color, int $Brightness, int $SystemState, int $AlarmState): void
    {
        // Signal lamps
        $signalLamps = json_decode($this->ReadPropertyString('DoorWindowStateSignalLamps'));
        if (!empty($signalLamps)) {
            $devices = 0;
            foreach ($signalLamps as $signalLamp) {
                if ($signalLamp->Use) {
                    $devices++;
                }
            }
            if ($devices != 0) {
                $i = 0;
                foreach ($signalLamps as $signalLamp) {
                    if ($signalLamp->Use) {
                        $i++;
                        $id = $signalLamp->ID;
                        if ($id != 0 && IPS_ObjectExists($id)) {
                            $deviceType = $signalLamp->Type;
                            $brightness = $signalLamp->Brightness;
                            // Check if the device is also used as a system state signal lamp
                            $systemStateSignalLamp = $this->CheckSystemStateSignalLamp($id);
                            // Abort if the system is armed or delayed armed
                            if ($systemStateSignalLamp) {
                                if ($SystemState == 1 || $SystemState == 2) {
                                    $this->SendDebug(__FUNCTION__, 'Abort, device is also used an alarm zone state indicator', 0);
                                    continue;
                                }
                            }
                            // Check if the device is also used as an alarm state signal lamp
                            $alarmStateSignalLamp = $this->CheckAlarmStateSignalLamp($id);
                            // Abort if we have an alarm
                            if ($alarmStateSignalLamp) {
                                if ($AlarmState == 1) {
                                    $this->SendDebug(__FUNCTION__, 'Abort, device is also used as an alarm state indicator', 0);
                                    continue;
                                }
                            }
                            switch ($State) {
                                // Closed
                                case 0:
                                    switch ($signalLamp->Options) {
                                        // 1: Closed
                                        // 3: Closed - Opened
                                        case 1:
                                        case 3:
                                            switch ($deviceType) {
                                                // Variable
                                                case 1:
                                                    @RequestAction($id, false);
                                                    break;

                                                // Script
                                                case 2:
                                                    @IPS_RunScriptEx($id, ['State' => 0]);
                                                    break;

                                                // HmIP
                                                case 3:
                                                case 4:
                                                case 5:
                                                    // Set color to green
                                                    $this->SetValue('DoorWindowStateColor', 2);
                                                    $this->SetValue('DoorWindowStateBrightness', $brightness);
                                                    $this->SetSignalLamp($id, 2, $brightness);
                                                    break;
                                            }
                                            break;

                                        // 2: Opened
                                        case 2:
                                            switch ($deviceType) {
                                                // HmIP
                                                case 3:
                                                case 4:
                                                case 5:
                                                    // Set color to off
                                                    $this->SetValue('DoorWindowStateColor', 0);
                                                    $this->SetValue('DoorWindowStateBrightness', 0);
                                                    $this->SetSignalLamp($id, 0, 0);
                                                    break;
                                            }
                                            break;

                                    }
                                    break;

                                // Opened
                                case 1:
                                    switch ($signalLamp->Options) {
                                        // 2: Opened
                                        // 3: Closed - Opened
                                        case 2:
                                        case 3:
                                            switch ($deviceType) {
                                                // Variable
                                                case 1:
                                                    @RequestAction($id, true);
                                                    break;

                                                // Script
                                                case 2:
                                                    @IPS_RunScriptEx($id, ['State' => 1]);
                                                    break;

                                                // HmIP
                                                case 3:
                                                case 4:
                                                case 5:
                                                    // Set color to blue
                                                    $this->SetValue('DoorWindowStateColor', 1);
                                                    $this->SetValue('DoorWindowStateBrightness', $brightness);
                                                    $this->SetSignalLamp($id, 1, $brightness);
                                                    break;

                                            }
                                            break;

                                        // 1: Closed
                                        case 1:
                                            switch ($deviceType) {
                                                // HmIP
                                                case 3:
                                                case 4:
                                                case 5:
                                                    // Set color to off
                                                    $this->SetValue('DoorWindowStateColor', 0);
                                                    $this->SetValue('DoorWindowStateBrightness', 0);
                                                    $this->SetSignalLamp($id, 0, 0);
                                                    break;

                                            }
                                            break;

                                    }
                                    break;

                                // 2: Other
                                case 2:
                                    switch ($deviceType) {
                                        // HmIP
                                        case 3:
                                        case 4:
                                        case 5:
                                            $this->SetValue('DoorWindowStateColor', $Color);
                                            $this->SetValue('DoorWindowStateBrightness', $Brightness);
                                            $this->SetSignalLamp($id, $Color, $Brightness);
                                            break;

                                    }
                                    break;

                            }
                        }
                    }
                    // Execution delay for next device
                    if ($devices > 1 && $i < $devices) {
                        IPS_Sleep(self::DELAY_MILLISECONDS);
                    }
                }
            }
        }
    }

    /**
     * Sets the alarm state signal lamp.
     *
     * @param int $State
     * 0    = no alarm
     * 1    = alarm
     * 2    = pre alarm
     * 3    = other
     *
     * @param int $Color
     * 0    = off
     * 1    = blue
     * 2    = green
     * 3    = turquoise
     * 4    = red
     * 5    = violet
     * 6    = yellow
     * 7    = white
     *
     * @param int $Brightness
     */
    public function SetAlarmStateSignalLamp(int $State, int $Color, int $Brightness): void
    {
        $signalLamps = json_decode($this->ReadPropertyString('AlarmStateSignalLamps'));
        if (!empty($signalLamps)) {
            $devices = 0;
            foreach ($signalLamps as $signalLamp) {
                if ($signalLamp->Use) {
                    $devices++;
                }
            }
            if ($devices != 0) {
                $i = 0;
                foreach ($signalLamps as $signalLamp) {
                    if ($signalLamp->Use) {
                        $i++;
                        $id = $signalLamp->ID;
                        if ($id != 0 && IPS_ObjectExists($id)) {
                            $deviceType = $signalLamp->Type;
                            $brightness = $signalLamp->Brightness;
                            // Check if the device is also used as a system state signal lamp
                            $systemStateSignalLamp = $this->CheckSystemStateSignalLamp($id);
                            // Abort if we have no alarm
                            if ($systemStateSignalLamp) {
                                if ($State == 0) {
                                    $this->SendDebug(__FUNCTION__, 'Abort, device is also used an alarm zone state indicator', 0);
                                    continue;
                                }
                            }
                            // Check if the device is also used as a door and window state signal lamp
                            $doorWindowStateSignalLamp = $this->CheckDoorWindowStateSignalLamp($id);
                            // Abort if we have no alarm
                            if ($doorWindowStateSignalLamp) {
                                if ($State == 0) {
                                    $this->SendDebug(__FUNCTION__, 'Abort, device is also used an alarm zone state indicator', 0);
                                    continue;
                                }
                            }
                            switch ($State) {
                                // No alarm
                                case 0:
                                    switch ($signalLamp->Options) {
                                        // 1: No alarm
                                        // 4: No alarm - pre alarm
                                        // 6: No alarm - alarm
                                        // 7: No alarm - pre alarm - alarm
                                        case 1:
                                        case 4:
                                        case 6:
                                        case 7:
                                            switch ($deviceType) {
                                                // Variable
                                                case 1:
                                                    @RequestAction($id, false);
                                                    break;

                                                // Script
                                                case 2:
                                                    @IPS_RunScriptEx($id, ['State' => 0]);
                                                    break;

                                                // HmIP
                                                case 3:
                                                case 4:
                                                case 5:
                                                    // Set color to green
                                                    $this->SetValue('AlarmStateColor', 2);
                                                    $this->SetValue('AlarmStateBrightness', $brightness);
                                                    $this->SetSignalLamp($id, 2, $brightness);
                                                    break;
                                            }
                                            break;

                                        // 2: Pre alarm
                                        // 3: Alarm
                                        // 5: Pre alarm - alarm
                                        case 2:
                                        case 3:
                                        case 5:
                                            switch ($deviceType) {
                                                // HmIP
                                                case 3:
                                                case 4:
                                                case 5:
                                                    // Set color to off
                                                    $this->SetValue('AlarmStateColor', 0);
                                                    $this->SetValue('AlarmStateBrightness', 0);
                                                    $this->SetSignalLamp($id, 0, 0);
                                                    break;
                                            }
                                            break;

                                    }
                                    break;

                                // Alarm
                                case 1:
                                    switch ($signalLamp->Options) {
                                        // 3: Alarm
                                        // 5: Pre alarm - alarm
                                        // 6: No alarm - alarm
                                        // 7: No alarm - pre alarm - alarm
                                        case 3:
                                        case 5:
                                        case 6:
                                        case 7:
                                            switch ($deviceType) {
                                                // Variable
                                                case 1:
                                                    @RequestAction($id, true);
                                                    break;

                                                // Script
                                                case 2:
                                                    @IPS_RunScriptEx($id, ['State' => 1]);
                                                    break;

                                                // HmIP
                                                case 3:
                                                case 4:
                                                case 5:
                                                    // Set color to red
                                                    $this->SetValue('AlarmStateColor', 4);
                                                    $this->SetValue('AlarmStateBrightness', $brightness);
                                                    $this->SetSignalLamp($id, 4, $brightness);
                                                    break;
                                            }
                                            break;

                                        // 1: No alarm
                                        // 2: Pre alarm
                                        // 4: No alarm - pre alarm
                                        case 1:
                                        case 2:
                                        case 4:
                                            switch ($deviceType) {
                                                // HmIP
                                                case 3:
                                                case 4:
                                                case 5:
                                                    // Set color to off
                                                    $this->SetValue('AlarmStateColor', 0);
                                                    $this->SetValue('AlarmStateBrightness', 0);
                                                    $this->SetSignalLamp($id, 0, 0);
                                                    break;
                                            }
                                            break;

                                    }
                                    break;

                                // Pre Alarm
                                case 2:
                                    switch ($signalLamp->Options) {
                                        // 2: Pre alarm
                                        // 4: No alarm - pre alarm
                                        // 5: Pre alarm - alarm
                                        // 7: No alarm - pre alarm - alarm
                                        case 2:
                                        case 4:
                                        case 5:
                                        case 7:
                                            switch ($deviceType) {
                                                // Script
                                                case 2:
                                                    @IPS_RunScriptEx($id, ['State' => 2]);
                                                    break;

                                                // HmIP
                                                case 3:
                                                case 4:
                                                case 5:
                                                    // Set color to yellow
                                                    $this->SetValue('AlarmStateColor', 6);
                                                    $this->SetValue('AlarmStateBrightness', $brightness);
                                                    $this->SetSignalLamp($id, 6, $brightness);
                                                    break;

                                            }
                                            break;

                                        // 1: No alarm
                                        // 3: Alarm
                                        // 6: No alarm - alarm
                                        case 1:
                                        case 3:
                                        case 6:
                                            switch ($deviceType) {
                                                // HmIP
                                                case 3:
                                                case 4:
                                                case 5:
                                                    // Set color to off
                                                    $this->SetValue('AlarmStateColor', 0);
                                                    $this->SetValue('AlarmStateBrightness', 0);
                                                    $this->SetSignalLamp($id, 0, 0);
                                                    break;
                                            }
                                            break;

                                    }
                                    break;

                                // 3: Other
                                case 3:
                                    switch ($deviceType) {
                                        // HmIP
                                        case 3:
                                        case 4:
                                        case 5:
                                            $this->SetValue('AlarmStateColor', $Color);
                                            $this->SetValue('AlarmStateBrightness', $Brightness);
                                            $this->SetSignalLamp($id, $Color, $Brightness);
                                            break;

                                    }
                                    break;

                            }
                        }
                    }
                    // Execution delay for next device
                    if ($devices > 1 && $i < $devices) {
                        IPS_Sleep(self::DELAY_MILLISECONDS);
                    }
                }
            }
        }
    }

    //#################### Private

    /**
     * Checks if the device is also used as a system state signal lamp.
     *
     * @param int $DeviceID
     * @return bool
     * false    = device is not a system state signal lamp.
     * true     = device is a system state signal lamp.
     */
    private function CheckSystemStateSignalLamp(int $DeviceID): bool
    {
        $device = false;
        $signalLamps = json_decode($this->ReadPropertyString('SystemStateSignalLamps'), true);
        if (!empty($signalLamps)) {
            $key = array_search($DeviceID, array_column($signalLamps, 'ID'));
            if ($key !== false) {
                if ($signalLamps[$key]['Use'] == true) {
                    $device = true;
                }
            }
        }
        return $device;
    }

    /**
     * Checks if the device is also used as a door and window state signal lamp.
     *
     * @param int $DeviceID
     * @return bool
     * false    = device is not a door and window state signal lamp.
     * true     = device is a door and window state signal lamp.
     */
    private function CheckDoorWindowStateSignalLamp(int $DeviceID): bool
    {
        $device = false;
        $signalLamps = json_decode($this->ReadPropertyString('DoorWindowStateSignalLamps'), true);
        if (!empty($signalLamps)) {
            $key = array_search($DeviceID, array_column($signalLamps, 'ID'));
            if ($key !== false) {
                if ($signalLamps[$key]['Use'] == true) {
                    $device = true;
                }
            }
        }
        return $device;
    }

    /**
     * Checks if the device is also used as an alarm state signal lamp.
     *
     * @param int $DeviceID
     * @return bool
     * false    = device is not a alarm state signal lamp.
     * true     = device is a alarm state signal lamp.
     */
    private function CheckAlarmStateSignalLamp(int $DeviceID): bool
    {
        $device = false;
        $signalLamps = json_decode($this->ReadPropertyString('AlarmStateSignalLamps'), true);
        if (!empty($signalLamps)) {
            $key = array_search($DeviceID, array_column($signalLamps, 'ID'));
            if ($key !== false) {
                if ($signalLamps[$key]['Use'] == true) {
                    $device = true;
                }
            }
        }
        return $device;
    }

    /**
     * Sets the color and brightness of the signal lamp.
     *
     * @param int $SignalLamp
     *
     * @param int $Color
     * 0    = off
     * 1    = blue
     * 2    = green
     * 3    = turquoise
     * 4    = red
     * 5    = violet
     * 6    = yellow
     * 7    = white
     *
     * @param int $Brightness
     */
    private function SetSignalLamp(int $SignalLamp = 0, int $Color = 0, int $Brightness = 0): void
    {
        // Semaphore Enter
        if (!IPS_SemaphoreEnter($this->InstanceID . '.SetSignalLamp', 5000)) {
            return;
        }
        // Signal lamp
        if ($SignalLamp != 0 && IPS_ObjectExists($SignalLamp)) {
            // Color
            $difference = $this->CheckValueDifference($SignalLamp, 'COLOR', (string) $Color);
            $this->SendDebug(__FUNCTION__, 'Color: ' . $Color . ', different color: ' . json_encode($difference), 0);
            if ($difference) {
                $setColor = @HM_WriteValueInteger($SignalLamp, 'COLOR', $Color);
                if (!$setColor) {
                    $errorMessage = 'Color could not be set to value: ' . $Color;
                    $this->LogMessage($errorMessage, 10205);
                    $this->SendDebug(__FUNCTION__, $errorMessage, 0);
                }
            }
            // Brightness
            $brightness = $Brightness / 100;
            $level = (float) str_replace(',', '.', $brightness);
            $setBrightness = @HM_WriteValueFloat($SignalLamp, 'LEVEL', $level);
            if (!$setBrightness) {
                $errorMessage = 'Brightness could not be set to value: ' . $level;
                $this->LogMessage($errorMessage, 10205);
                $this->SendDebug(__FUNCTION__, $errorMessage, 0);
            }
        }
        // Semaphore leave
        IPS_SemaphoreLeave($this->InstanceID . '.SetSignalLamp');
    }

    /**
     * Checks if the value is different.
     *
     * @param int $SignalLamp
     * @param string $ChannelName
     * @param string $Value
     * @return bool
     */
    private function CheckValueDifference(int $SignalLamp, string $ChannelName, string $Value): bool
    {
        $difference = true;
        $channelParameters = IPS_GetChildrenIDs($SignalLamp);
        if (!empty($channelParameters)) {
            foreach ($channelParameters as $channelParameter) {
                $ident = IPS_GetObject($channelParameter)['ObjectIdent'];
                if ($ident == $ChannelName) {
                    if (GetValue($channelParameter) == $Value) {
                        $difference = false;
                    }
                }
            }
        }
        return $difference;
    }
}
