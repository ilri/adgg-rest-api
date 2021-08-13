<?php

namespace App\Tests\API;

use App\Tests\AuthApiTestCase;

class AnimalEventTest extends AuthApiTestCase
{
    public function testGetCollection()
    {
        $response = $this->client->request('GET', '/api/animal_events');
        $this->assertResponseStatusCodeSame(401);

        $response = $this->client->request('GET', '/api/animal_events', ['auth_bearer' => $this->token]);
        $this->assertResponseIsSuccessful();
        $json = $response->toArray();
        // we have created 40 animal events in the fixtures
        $this->assertEquals(40, $json['hydra:totalItems']);
    }

    public function testPostCollection()
    {
        $response = $this->client->request('POST', '/api/animal_events');
        $this->assertResponseStatusCodeSame(401);

        $response = $this->client->request(
            'POST',
            '/api/animal_events',
            [
                'auth_bearer' => $this->token,
                'json' => [
                    'animal' => '/api/animals/1',
                    'eventType' => 2, // milking
                    'countryId' => 1,
                    'eventDate' => '2020-02-03',
                    'additionalAttributes' => [
                        '59' => '10',
                        '61' => '12',
                        '68' => '10',
                    ],
                ],
            ]
        );
        $this->assertResponseIsSuccessful();
        $this->assertResponseHeaderSame('content-type', 'application/ld+json; charset=utf-8');
        $json = $response->toArray();
        $this->assertArrayHasKey('eventType', $json);
        $this->assertArrayHasKey('countryId', $json);
        $this->assertArrayHasKey('eventDate', $json);
        $this->assertArrayHasKey('additionalAttributes', $json);
        // the same payload should not create a new entry
        $response = $this->client->request(
            'POST',
            '/api/animal_events',
            [
                'auth_bearer' => $this->token,
                'json' => [
                    'animal' => '/api/animals/1',
                    'eventType' => 2, // milking
                    'countryId' => 1,
                    'eventDate' => '2020-02-03',
                    'additionalAttributes' => [
                        '59' => '10',
                        '61' => '12',
                        '68' => '10',
                    ],
                ],
            ]
        );
        $this->assertResponseStatusCodeSame(422);
    }

    public function testGetItem()
    {
        $response = $this->client->request('GET', '/api/animal_events/1');
        $this->assertResponseStatusCodeSame(401);

        $response = $this->client->request('GET', '/api/animal_events/1', ['auth_bearer' => $this->token]);
        $this->assertResponseIsSuccessful();
        $this->assertResponseHeaderSame('content-type', 'application/ld+json; charset=utf-8');
        $json = $response->toArray();
        $this->assertArrayHasKey('animal', $json);
        $this->assertArrayHasKey('eventType', $json);
        $this->assertArrayHasKey('countryId', $json);
    }

    public function testPutItem()
    {
        $response = $this->client->request('PUT', '/api/animal_events/1');
        $this->assertResponseStatusCodeSame(401);

        $response = $this->client->request(
            'PUT',
            '/api/animal_events/2',
            [
                'auth_bearer' => $this->token,
                'json' => [
                    'animal' => '/api/animals/1',
                    'eventType' => 2, // milking
                    'countryId' => 2,
                    'eventDate' => '2020-02-03',
                    'additionalAttributes' => [
                        '59' => '5',
                        '61' => '6',
                        '68' => '5',
                    ],
                ],
            ]
        );
        $this->assertResponseIsSuccessful();
        $this->assertResponseHeaderSame('content-type', 'application/ld+json; charset=utf-8');
//        $json = $response->toArray();
//        $this->assertEquals('5', $json['additionalAttributes']['59']);
//        $this->assertEquals('6', $json['additionalAttributes']['61']);
//        $this->assertEquals('5', $json['additionalAttributes']['68']);
    }

    public function testDeleteItem()
    {
        $response = $this->client->request('DELETE', '/api/animal_events/1');
        $this->assertResponseStatusCodeSame(405);

        $response = $this->client->request('DELETE', '/api/animal_events/1', ['auth_bearer' => $this->token]);
        $this->assertResponseStatusCodeSame(405);
    }

    public function testPatchItem()
    {
        $response = $this->client->request('PATCH', '/api/animal_events/1');
        $this->assertResponseStatusCodeSame(401);

        $response = $this->client->request(
            'PATCH',
            '/api/animal_events/11',
            [
                'auth_bearer' => $this->token,
                'headers' => [
                    'content-type' => 'application/merge-patch+json',
                ],
                'json' => [
                    'additionalAttributes' => [
                        '59' => '8',
                        '61' => '10',
                        '68' => '8',
                    ],
                ],
            ]
        );
        $this->assertResponseIsSuccessful();
        $this->assertResponseHeaderSame('content-type', 'application/ld+json; charset=utf-8');
//        $json = $response->toArray();
//        $this->assertEquals('8', $json['additionalAttributes']['59']);
//        $this->assertEquals('10', $json['additionalAttributes']['61']);
//        $this->assertEquals('8', $json['additionalAttributes']['68']);
    }

    public function testGetCalvingEvents()
    {
        $response = $this->client->request('GET', '/api/animal_events/calving_events');
        $this->assertResponseStatusCodeSame(401);

        $response = $this->client->request(
            'GET',
            '/api/animal_events/calving_events',
            ['auth_bearer' => $this->token]
        );
        $this->assertResponseIsSuccessful();
        $this->assertResponseHeaderSame(
            'content-type',
            'application/ld+json; charset=utf-8'
        );
        $json = $response->toArray();
        // we have created 10 calving events in the fixtures
        $this->assertEquals(10, $json['hydra:totalItems']);
    }

    public function testGetMilkingEvents()
    {
        $response = $this->client->request('GET', '/api/animal_events/milking_events');
        $this->assertResponseStatusCodeSame(401);

        $response = $this->client->request(
            'GET',
            '/api/animal_events/milking_events',
            ['auth_bearer' => $this->token]
        );
        $this->assertResponseIsSuccessful();
        $this->assertResponseHeaderSame(
            'content-type',
            'application/ld+json; charset=utf-8'
        );
        $json = $response->toArray();
        // we have created 10 milking events in the fixtures
        $this->assertEquals(10, $json['hydra:totalItems']);
    }

    public function testGetExitsEvents()
    {
        $response = $this->client->request('GET', '/api/animal_events/exits_events');
        $this->assertResponseStatusCodeSame(401);

        $response = $this->client->request(
            'GET',
            '/api/animal_events/exits_events',
            ['auth_bearer' => $this->token]
        );
        $this->assertResponseIsSuccessful();
        $this->assertResponseHeaderSame(
            'content-type',
            'application/ld+json; charset=utf-8'
        );
        $json = $response->toArray();
        // we have created 10 exits events in the fixtures
        $this->assertGreaterThanOrEqual(10, $json['hydra:totalItems']);
    }

    public function testPropertyFilter()
    {
        //Specifying a property (e.g. eventType) with the PropertyFilter should
        //successfully omit all other properties
        $response = $this->client->request(
            'GET',
            '/api/animal_events?properties[]=eventType',
            ['auth_bearer' => $this->token]
        );
        $this->assertResponseIsSuccessful();

        $json = $response->toArray();
        $this->assertArrayHasKey('eventType', $json['hydra:member'][0]);
        $this->assertArrayNotHasKey('eventDate', $json['hydra:member'][0]);
    }

    public function testSearchFilter()
    {
        $response = $this->client->request(
            'GET',
            '/api/animal_events?animal=/api/animals/1',
            ['auth_bearer' => $this->token]
        );
        $this->assertResponseIsSuccessful();

        $json = $response->toArray();
        $this->assertEquals(4, $json['hydra:totalItems']);
    }

    public function testDateFilter()
    {
        $response = $this->client->request(
            'GET',
            '/api/animal_events?eventDate[strictly_before]=2021-01-01',
            ['auth_bearer' => $this->token]
        );
        $this->assertResponseIsSuccessful();

        $json = $response->toArray();
        $this->assertEquals(0, $json['hydra:totalItems']);
    }
}
