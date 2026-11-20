<?php

class HealthService
{
    public function getStatus(): array
    {
        return [
            'app' => 'DronePilot Services API',
            'message' => 'The backend is running.'
        ];
    }
}
