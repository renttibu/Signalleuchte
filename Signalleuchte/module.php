<?php

/*
 * @module      Signalleuchte
 *
 * @prefix      SIGL
 *
 * @file        module.php
 *
 * @author      Ulrich Bittner
 * @copyright   (c) 2020
 * @license    	CC BY-NC-SA 4.0
 *              https://creativecommons.org/licenses/by-nc-sa/4.0/
 *
 * @version     4.00-1
 * @date        2020-01-18, 18:00, 1579366800
 * @review      2020-01-29, 18:00
 *
 * @see         https://github.com/ubittner/Signalleuchte/
 *
 * @guids       Library
 *              {CF6B75A5-C573-7030-0D75-2F50A8A42B73}
 *
 *              Signalleuchte
 *             	{CF6B75A5-C573-7030-0D75-2F50A8A42B73}
 */

// Declare
declare(strict_types=1);

// Include
include_once __DIR__ . '/helper/autoload.php';

class Signalleuchte extends IPSModule
{
    // Helper
    use SIGL_signalLamp;

    // Constants
    private const DELAY_MILLISECONDS = 250;

    public function Create()
    {
        // Never delete this line!
        parent::Create();

        // Register properties
        $this->RegisterProperties();

        // Create profiles
        $this->CreateProfiles();

        // Register variables
        $this->RegisterVariables();
    }

    public function ApplyChanges()
    {
        // Wait until IP-Symcon is started
        $this->RegisterMessage(0, IPS_KERNELSTARTED);

        // Never delete this line!
        parent::ApplyChanges();

        // Check runlevel
        if (IPS_GetKernelRunlevel() != KR_READY) {
            return;
        }

        // Set options
        $this->SetOptions();
    }

    protected function KernelReady()
    {
        $this->ApplyChanges();
    }

    public function Destroy()
    {
        // Never delete this line!
        parent::Destroy();

        // Delete profiles
        $this->DeleteProfiles();
    }

    public function MessageSink($TimeStamp, $SenderID, $Message, $Data)
    {
        $this->SendDebug(__FUNCTION__, 'SenderID: ' . $SenderID . ', Message: ' . $Message . ' Data: ' . print_r($Data, true), 0);
        switch ($Message) {
            case IPS_KERNELSTARTED:
                $this->KernelReady();
                break;

        }
    }

    //#################### Request Action

    public function RequestAction($Ident, $Value)
    {
        switch ($Ident) {
            case 'SystemStateColor':
                $this->SetValue('SystemStateColor', $Value);
                $brightness = (int) $this->GetValue('SystemStateBrightness');
                $this->SetSystemStateSignalLamp(3, $Value, $brightness, 0);
                break;

            case 'SystemStateBrightness':
                $this->SetValue('SystemStateBrightness', $Value);
                $color = (int) $this->GetValue('SystemStateColor');
                $this->SetSystemStateSignalLamp(3, $color, $Value, 0);
                break;

            case 'DoorWindowStateColor':
                $this->SetValue('DoorWindowStateColor', $Value);
                $brightness = (int) $this->GetValue('DoorWindowStateBrightness');
                $this->SetDoorWindowStateSignalLamp(2, $Value, $brightness, 0, 0);
                break;

            case 'DoorWindowStateBrightness':
                $this->SetValue('DoorWindowStateBrightness', $Value);
                $color = (int) $this->GetValue('DoorWindowStateColor');
                $this->SetDoorWindowStateSignalLamp(2, $color, $Value, 0, 0);
                break;

            case 'AlarmStateColor':
                $this->SetValue('AlarmStateColor', $Value);
                $brightness = (int) $this->GetValue('AlarmStateBrightness');
                $this->SetAlarmStateSignalLamp(3, $Value, $brightness);
                break;

            case 'AlarmStateBrightness':
                $this->SetValue('AlarmStateBrightness', $Value);
                $color = (int) $this->GetValue('AlarmStateColor');
                $this->SetAlarmStateSignalLamp(3, $color, $Value);
                break;

        }
    }

    //#################### Private

    private function RegisterProperties(): void
    {
        // Visibility
        $this->RegisterPropertyBoolean('EnableSystemStateColor', true);
        $this->RegisterPropertyBoolean('EnableSystemStateBrightness', true);
        $this->RegisterPropertyBoolean('EnableDoorWindowStateColor', true);
        $this->RegisterPropertyBoolean('EnableDoorWindowStateBrightness', true);
        $this->RegisterPropertyBoolean('EnableAlarmStateColor', true);
        $this->RegisterPropertyBoolean('EnableAlarmStateBrightness', true);

        // System state
        $this->RegisterPropertyString('SystemStateSignalLamps', '[]');

        // Door and window state
        $this->RegisterPropertyString('DoorWindowStateSignalLamps', '[]');

        // Alarm state
        $this->RegisterPropertyString('AlarmStateSignalLamps', '[]');
    }

    private function CreateProfiles(): void
    {
        $profile = 'SIGL.' . $this->InstanceID . '.Color';
        if (!IPS_VariableProfileExists($profile)) {
            IPS_CreateVariableProfile($profile, 1);
        }
        IPS_SetVariableProfileIcon($profile, '');
        IPS_SetVariableProfileAssociation($profile, 0, 'Aus', 'Bulb', 0);
        IPS_SetVariableProfileAssociation($profile, 1, 'Blau', 'Bulb', 0x0000FF);
        IPS_SetVariableProfileAssociation($profile, 2, 'Grün', 'Bulb', 0x00FF00);
        IPS_SetVariableProfileAssociation($profile, 3, 'Türkis', 'Bulb', 0x01DFD7);
        IPS_SetVariableProfileAssociation($profile, 4, 'Rot', 'Bulb', 0xFF0000);
        IPS_SetVariableProfileAssociation($profile, 5, 'Violett', 'Bulb', 0xB40486);
        IPS_SetVariableProfileAssociation($profile, 6, 'Gelb', 'Bulb', 0xFFFF00);
        IPS_SetVariableProfileAssociation($profile, 7, 'Weiß', 'Bulb', 0xFFFFFF);
    }

    private function DeleteProfiles(): void
    {
        $profiles = ['Color'];
        if (!empty($profiles)) {
            foreach ($profiles as $profile) {
                $profileName = 'SIGL.' . $this->InstanceID . '.' . $profile;
                if (IPS_VariableProfileExists($profileName)) {
                    IPS_DeleteVariableProfile($profileName);
                }
            }
        }
    }

    private function RegisterVariables(): void
    {
        $colorProfileName = 'SIGL.' . $this->InstanceID . '.Color';

        // System state
        // Color
        $this->RegisterVariableInteger('SystemStateColor', 'Systemstatus Farbe', $colorProfileName, 1);
        $this->EnableAction('SystemStateColor');
        $this->SetValue('SystemStateColor', 0);
        // Brightness
        $this->RegisterVariableInteger('SystemStateBrightness', 'Systemstatus Helligkeit', '~Intensity.100', 2);
        $this->EnableAction('SystemStateBrightness');
        $this->SetValue('SystemStateBrightness', 10);

        // Door and window state
        // Color
        $this->RegisterVariableInteger('DoorWindowStateColor', 'Tür- /Fensterstatus Farbe', $colorProfileName, 3);
        $this->EnableAction('DoorWindowStateColor');
        $this->SetValue('DoorWindowStateColor', 0);
        // Brightness
        $this->RegisterVariableInteger('DoorWindowStateBrightness', 'Tür- / Fensterstatus Helligkeit', '~Intensity.100', 4);
        $this->EnableAction('DoorWindowStateBrightness');
        $this->SetValue('DoorWindowStateBrightness', 10);

        // Alarm state
        // Color
        $this->RegisterVariableInteger('AlarmStateColor', 'Alarmstatus Farbe', $colorProfileName, 5);
        $this->EnableAction('AlarmStateColor');
        $this->SetValue('AlarmStateColor', 0);
        // Brightness
        $this->RegisterVariableInteger('AlarmStateBrightness', 'Alarmstatus Helligkeit', '~Intensity.100', 6);
        $this->EnableAction('AlarmStateBrightness');
        $this->SetValue('AlarmStateBrightness', 10);
    }

    private function SetOptions(): void
    {
        // System state
        // Color
        $id = $this->GetIDForIdent('SystemStateColor');
        $use = $this->ReadPropertyBoolean('EnableSystemStateColor');
        IPS_SetHidden($id, !$use);
        // Brightness
        $id = $this->GetIDForIdent('SystemStateBrightness');
        $use = $this->ReadPropertyBoolean('EnableSystemStateBrightness');
        IPS_SetHidden($id, !$use);

        // Door and window state
        // Color
        $id = $this->GetIDForIdent('DoorWindowStateColor');
        $use = $this->ReadPropertyBoolean('EnableDoorWindowStateColor');
        IPS_SetHidden($id, !$use);
        // Brightness
        $id = $this->GetIDForIdent('DoorWindowStateBrightness');
        $use = $this->ReadPropertyBoolean('EnableDoorWindowStateBrightness');
        IPS_SetHidden($id, !$use);

        // Alarm state
        // Color
        $id = $this->GetIDForIdent('AlarmStateColor');
        $use = $this->ReadPropertyBoolean('EnableAlarmStateColor');
        IPS_SetHidden($id, !$use);
        // Brightness
        $id = $this->GetIDForIdent('AlarmStateBrightness');
        $use = $this->ReadPropertyBoolean('EnableAlarmStateBrightness');
        IPS_SetHidden($id, !$use);
    }
}