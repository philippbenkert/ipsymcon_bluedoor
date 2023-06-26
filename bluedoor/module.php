<?php
declare(strict_types=1);
class BlueDoor extends IPSModule
{
    public function Create()
    {
        //Never delete this line!
        parent::Create();
        
        $this->RegisterPropertyString("DeviceAddress", "");
        $this->RegisterAttributeString("DiscoveredDevices", "[]");
    }

    public function ApplyChanges()
    {
        //Never delete this line!
        parent::ApplyChanges();
        
        // Erstellt eine Variable zur Speicherung des RSSI-Werts, falls noch nicht vorhanden
        if (!@$this->GetIDForIdent('RSSI')) {
            $this->RegisterVariableFloat('RSSI', 'RSSI', '');
        }
    }

    public function Update()
    {
        $address = $this->ReadPropertyString("DeviceAddress");

        if (!empty($address)) {
            $output = [];
            $return_var = 0;
            exec("hcitool rssi " . escapeshellarg($address), $output, $return_var);

            if ($return_var == 0) {
                if (preg_match("/RSSI return value: (\-?\d+)/", $output[0], $matches)) {
                    $rssi = intval($matches[1]);
                    SetValue($this->GetIDForIdent('RSSI'), $rssi);
                }
            } else {
                IPS_LogMessage('BlueDoor', 'Failed to get RSSI for device: ' . $address);
            }
        }
    }

    public function ScanDevices()
    {
        $output = [];
        $return_var = 0;
        exec("hcitool scan", $output, $return_var);

        if ($return_var == 0) {
            $devices = [];

            foreach ($output as $line) {
                if (preg_match("/^([0-9A-F:]{17})\s+(.*)$/", $line, $matches)) {
                    $devices[] = [
                        "address" => $matches[1],
                        "name" => $matches[2],
                    ];
                }
            }

            $this->WriteAttributeString("DiscoveredDevices", json_encode($devices));
        } else {
            IPS_LogMessage('BlueDoor', 'Failed to scan for devices');
        }
    }
    
    public function GetConfigurationForm()
    {
        // Initialize the form
        $form = [
            "elements" => [],
            "actions" => [],
            "status" => []
        ];
        
        // Add the elements
        $form["elements"][] = [
            "type" => "Select",
            "name" => "DeviceAddress",
            "caption" => "Device",
            "options" => array_map(function($device) {
                return [
                    "caption" => $device["name"],
                    "value" => $device["address"]
                ];
            }, json_decode($this->ReadAttributeString("DiscoveredDevices"), true))
        ];
        
        // Add the actions
        $form["actions"][] = [
            "type" => "Button",
            "label" => "Scan Devices",
            "onClick" => 'BlueDoor_ScanDevices($id);'
        ];
        
        //Add the status
        $form["status"][] = [
            "code" => 102,
            "icon" => "active",
            "caption" => "BlueDoor active"
        ];
        
        return json_encode($form);
    }
}
