<?php

class GetFreshdeskConfigTask extends BuildTask
{
    public function run($request)
    {
        $ticketFields = [];
        $agents = [];
        $freshdesk = \FreshdeskAPI::create();

        $statusResult = $freshdesk->APICall('GET', FRESHDESK_API_BASEURL, '/api/v2/ticket_fields?type=default_status');

        if ($statusResult && $statusResult->getStatusCode() == '200') {
            $ticketFields = json_decode($statusResult->getBody()->getContents(), true);

            foreach ($ticketFields as $ticketField) {
                $this->doStatuses($ticketField['choices']);
            }
        }

        $agentResult = $freshdesk->APICall('GET', FRESHDESK_API_BASEURL, '/api/v2/agents');
        
        if ($agentResult && $agentResult->getStatusCode() == '200') {
            $agents = json_decode($result->getBody()->getContents(), true);
            $this->doAgents($agents);
        }
    }

    private function doStatuses($statuses)
    {
        $existing =  FreshdeskTicketStatuses::get();
        
        $updated = 0;
        $created = 0;

        foreach ($statuses as $id=>$status) {
            $search = $existing->find('StatusId', $id);

            if ($search) {
                if ($search->Name != $status[0]) {
                    $search->Name = $status[0];
                    $search->write();
                    $updated ++;
                }
            } else {
                $newStatus = new FreshdeskTicketStatuses();
                $newStatus->StatusId = $id;
                $newStatus->Name = $status[0];
                $newStatus->write();
                $created ++;    
            }
        }
        $this->log("$updated Statuses updated, $created Statuses created.");
    }

    private function doAgents($agents)
    {
        $existing =  FreshdeskAgents::get();

        $updated = 0;
        $created = 0;

        foreach ($agents as $agent) {
            $search = $existing->find('AgentId', $agent['id']);

            if ($search) {
                if ($search->Name != $agent['contact']['name']) {
                    $search->Name = $agent['contact']['name'];
                    $search->write();
                    $updated ++;
                }
            } else {
                $newAgent = new FreshdeskAgents();
                $newAgent->AgentId = $agent['id'];
                $newAgent->Name = $agent['contact']['name'];
                $newAgent->write();
                $created ++;
            }
        }
        $this->log("$updated Agents updated, $created Agents created.");
    }

    private function log($message)
    {
        if (Director::is_cli()) {
            $message = str_replace("\n", PHP_EOL, $message);
            echo $message.PHP_EOL;
        } else {
            $message = str_replace("\n", '<br>', $message);
            echo $message.'<br>';
        }
    }
}