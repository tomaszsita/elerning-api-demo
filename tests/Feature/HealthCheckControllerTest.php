<?php

declare(strict_types = 1);

namespace App\Tests\Feature;

class HealthCheckControllerTest extends AbstractFeature
{
    public function testHealthEndpoint(): void
    {
        $this->client->request('GET', '/health');

        $this->assertResponseStatusCodeSame(200);

        $responseData = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('status', $responseData);
        $this->assertEquals('ok', $responseData['status']);
        $this->assertArrayHasKey('timestamp', $responseData);
        $this->assertArrayHasKey('version', $responseData);
        $this->assertEquals('1.0.0', $responseData['version']);

        // Check if timestamp is valid
        $timestamp = \DateTimeImmutable::createFromFormat('Y-m-d H:i:s', $responseData['timestamp']);
        $this->assertInstanceOf(\DateTimeImmutable::class, $timestamp);
    }
}
