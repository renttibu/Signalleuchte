<?php

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
     * @throws Exception
     * @throws Exception
     */
    public function SetSystemStateSignalLamp(int $State, int $Color, int $Brightness, int $AlarmState): void
    {
        if ($this->CheckMaintenanceMode()) {
            return;
        }
        if ($this->GetValue('NightMode')) {
            return;
        }
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
     * @throws Exception
     * @throws Exception
     */
    public function SetDoorWindowStateSignalLamp(int $State, int $Color, int $Brightness, int $SystemState, int $AlarmState): void
    {
        if ($this->CheckMaintenanceMode()) {
            return;
        }
        if ($this->GetValue('NightMode')) {
            return;
        }
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
     * @throws Exception
     * @throws Exception
     */
    public function SetAlarmStateSignalLamp(int $State, int $Color, int $Brightness): void
    {
        if ($this->CheckMaintenanceMode()) {
            return;
        }
        if ($this->GetValue('NightMode')) {
            return;
        }
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

    /**
     * Toggles the night mode
     *
     * @param bool $State
     * false    night mode is off, normal mode
     * true     night mode is on, device is off
     * @throws Exception
     * @throws Exception
     */
    public function ToggleNightMode(bool $State): void
    {
        // Off
        if (!$State) {
            $this->SetValue('NightMode', false);
        }

        // On
        if ($State) {
            $this->SetValue('NightMode', true);
            $devices = [];
            // System state
            $signalLamps = json_decode($this->ReadPropertyString('SystemStateSignalLamps'));
            if (!empty($signalLamps)) {
                foreach ($signalLamps as $signalLamp) {
                    if ($signalLamp->Use) {
                        array_push($devices, $signalLamp->ID);
                    }
                }
            }
            // Door window state
            $signalLamps = json_decode($this->ReadPropertyString('DoorWindowStateSignalLamps'));
            if (!empty($signalLamps)) {
                foreach ($signalLamps as $signalLamp) {
                    if ($signalLamp->Use) {
                        array_push($devices, $signalLamp->ID);
                    }
                }
            }
            // Alarm state
            $signalLamps = json_decode($this->ReadPropertyString('AlarmStateSignalLamps'));
            if (!empty($signalLamps)) {
                foreach ($signalLamps as $signalLamp) {
                    if ($signalLamp->Use) {
                        array_push($devices, $signalLamp->ID);
                    }
                }
            }
            if (empty($devices)) {
                return;
            }
            $devices = array_unique($devices);
            $count = count($devices);
            $i = 0;
            foreach ($devices as $device) {
                $i++;
                $this->SetSignalLamp($device, 0, 0);
                // Execution delay for next device
                if ($count > 1 && $i < $count) {
                    IPS_Sleep(self::DELAY_MILLISECONDS);
                }
            }
        }
    }

    #################### Private

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
     * @throws Exception
     * @throws Exception
     */
    private function SetSignalLamp(int $SignalLamp = 0, int $Color = 0, int $Brightness = 0): void
    {
        if ($this->CheckMaintenanceMode()) {
            return;
        }
        // Semaphore Enter
        if (!IPS_SemaphoreEnter($this->InstanceID . '.SetSignalLamp', 5000)) {
            return;
        }
        // Signal lamp
        if ($SignalLamp != 0 && IPS_ObjectExists($SignalLamp)) {
            $colorDifference = $this->CheckColorDifference($SignalLamp, $Color);
            if ($colorDifference) {
                // Color
                $this->SendDebug(__FUNCTION__, 'Farbwert wird auf den Wert ' . $Color . ' gesetzt.', 0);
                $setColor = @HM_WriteValueInteger($SignalLamp, 'COLOR', $Color);
                if (!$setColor) {
                    $errorMessage = 'Farbwert konnte nicht auf den Wert ' . $Color . ' gesetzt werden.';
                    $this->LogMessage($errorMessage, 10205);
                    $this->SendDebug(__FUNCTION__, $errorMessage, 0);
                }
            }
            // Brightness
            $brightness = $Brightness / 100;
            $level = (float) str_replace(',', '.', $brightness);
            $levelDifference = $this->CheckLevelDifference($SignalLamp, $level);
            if ($levelDifference) {
                $this->SendDebug(__FUNCTION__, 'Helligkeit wird auf den Wert ' . $level . ' gesetzt.', 0);
                $setBrightness = @HM_WriteValueFloat($SignalLamp, 'LEVEL', $level);
                if (!$setBrightness) {
                    $errorMessage = 'Helligkeit konnte nicht auf den Wert ' . $level . ' gesetzt werden.';
                    $this->LogMessage($errorMessage, 10205);
                    $this->SendDebug(__FUNCTION__, $errorMessage, 0);
                }
            }
        }
        // Semaphore leave
        IPS_SemaphoreLeave($this->InstanceID . '.SetSignalLamp');
    }

    /**
     * Checks if the color value is different.
     *
     * @param int $SignalLamp
     * @param int $Value
     * @return bool
     */
    private function CheckColorDifference(int $SignalLamp, int $Value): bool
    {
        $this->SendDebug(__FUNCTION__, 'Angefragter Farbwert: ' . $Value, 0);
        $colorDifference = true;
        $channelParameters = IPS_GetChildrenIDs($SignalLamp);
        if (!empty($channelParameters)) {
            foreach ($channelParameters as $channelParameter) {
                $ident = IPS_GetObject($channelParameter)['ObjectIdent'];
                if ($ident == 'COLOR') {
                    $actualValue = GetValueInteger($channelParameter);
                    $this->SendDebug(__FUNCTION__, 'Aktueller Farbwert: ' . $actualValue, 0);
                    if ($actualValue == $Value) {
                        $colorDifference = false;
                    }
                }
            }
        }
        $this->SendDebug(__FUNCTION__, 'Unterschiedliche Farbwerte: ' . json_encode($colorDifference), 0);
        return $colorDifference;
    }

    /**
     * Checks if the level value is different.
     *
     * @param int $SignalLamp
     * @param float $Value
     * @return bool
     */
    private function CheckLevelDifference(int $SignalLamp, float $Value): bool
    {
        $this->SendDebug(__FUNCTION__, 'Angefragter Helligkeitswert: ' . $Value, 0);
        $levelDifference = true;
        $channelParameters = IPS_GetChildrenIDs($SignalLamp);
        if (!empty($channelParameters)) {
            foreach ($channelParameters as $channelParameter) {
                $ident = IPS_GetObject($channelParameter)['ObjectIdent'];
                if ($ident == 'LEVEL') {
                    $actualValue = GetValueFloat($channelParameter);
                    $this->SendDebug(__FUNCTION__, 'Aktueller Helligkeitswert: ' . $actualValue, 0);
                    if ($actualValue == $Value) {
                        $levelDifference = false;
                    }
                }
            }
        }
        $this->SendDebug(__FUNCTION__, 'Unterschiedliche Helligkeitswerte: ' . json_encode($levelDifference), 0);
        return $levelDifference;
    }
}
