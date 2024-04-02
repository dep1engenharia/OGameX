<?php

namespace Feature;

use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Support\Carbon;
use Tests\AccountTestCase;

/**
 * Test that the unit queue works as expected.
 */
class UnitQueueTest extends AccountTestCase
{
    /**
     * Verify that building more than one of a ship works as expected.
     * @throws BindingResolutionException
     */
    public function testUnitQueueShips(): void
    {
        // Add resources to planet that test requires.
        $this->planetAddResources(['metal' => 30000, 'crystal' => 10000]);
        // Set the robotics factory to level 2
        $this->planetSetObjectLevel(14, 2, true);
        // Set shipyard to level 1.
        $this->planetSetObjectLevel(21, 1, true);
        // Set the research lab to level 1.
        $this->planetSetObjectLevel(31, 1, true);
        // Set energy technology to level 1.
        $this->playerSetResearchLevel(113, 1);
        // Set combustion drive to level 1.
        $this->playerSetResearchLevel(115, 1);

        // Set the current time to a specific moment for testing
        $testTime = Carbon::create(2024, 1, 1, 12, 0, 0);
        Carbon::setTestNow($testTime);

        // ---
        // Step 1: Issue a request to build 10 light fighters
        // ---
        $response = $this->post('/shipyard/add-buildrequest', [
            'token' => csrf_token(),
            'type' => '204', // Light fighter
            'amount' => 10,
            'planet_id' => $this->currentPlanetId,
        ]);

        // Assert the response status is successful (302 redirect).
        $response->assertStatus(302);

        // ---
        // Step 2: Verify the ships are in the build queue
        // ---
        $response = $this->get('/shipyard');
        $response->assertStatus(200);
        $pattern = '/<span\s+class="level">\s*<span\s+class="textlabel">\s*Light\s*Fighter\s*<\/span>\s*0\s*<\/span>/';
        $result = preg_match($pattern, $response->getContent());
        $this->assertTrue($result === 1, 'Light Fighter is not at 0 units directly after build request issued.');

        // ---
        // Step 3: Verify the ships are still in the build queue 1 minute later.
        // ---
        $testTime = Carbon::create(2024, 1, 1, 12, 1, 0);
        Carbon::setTestNow($testTime);

        $response = $this->get('/shipyard');
        $response->assertStatus(200);
        $pattern = '/<span\s+class="level">\s*<span\s+class="textlabel">\s*Light\s*Fighter\s*<\/span>\s*0\s*<\/span>/';
        $result = preg_match($pattern, $response->getContent());
        $this->assertTrue($result === 1, 'Light Fighter is not at 0 units 1m after build request issued.');

        // ---
        // Step 4: Verify that some ships are finished 30 minute later.
        // ---
        $testTime = Carbon::create(2024, 1, 1, 12, 20, 0);
        Carbon::setTestNow($testTime);

        $response = $this->get('/shipyard');
        $response->assertStatus(200);
        $pattern = '/<span\s+class="level">\s*<span\s+class="textlabel">\s*Light\sFighter\s*<\/span>\s*3\s*<\/span>/';
        $result = preg_match($pattern, $response->getContent());
        $this->assertTrue($result === 1, 'Light Fighter build job has not completed exactly 3 units 15m after build request issued.');

        // ---
        // Step 5: Verify that ALL ships are finished 15 minute later.
        // ---
        $testTime = Carbon::create(2024, 1, 1, 14, 0, 0);
        Carbon::setTestNow($testTime);

        $response = $this->get('/shipyard');
        $response->assertStatus(200);
        $pattern = '/<span\s+class="level">\s*<span\s+class="textlabel">\s*Light\sFighter\s*<\/span>\s*10\s*<\/span>/';
        $result = preg_match($pattern, $response->getContent());
        $this->assertTrue($result === 1, 'Light Fighter build job is not finished yet 2h after build request issued.');
    }

    /**
     * Verify that adding three different build jobs and waiting for them all to complete works as expected.
     * @throws BindingResolutionException
     */
    public function testUnitQueueShipsMultiQueues(): void
    {
        // Add resources to planet that test requires.
        // For 5 light fighters
        $this->planetAddResources(['metal' => 15000, 'crystal' => 5000]);
        // For 10 solar satellites
        $this->planetAddResources(['crystal' => 20000, 'deuterium' => 5000]);
        // Set the robotics factory to level 2
        $this->planetSetObjectLevel(14, 2, true);
        // Set shipyard to level 1.
        $this->planetSetObjectLevel(21, 1, true);
        // Set the research lab to level 1.
        $this->planetSetObjectLevel(31, 1, true);
        // Set energy technology to level 1.
        $this->playerSetResearchLevel(113, 1);
        // Set combustion drive to level 1.
        $this->playerSetResearchLevel(115, 1);

        // Set the current time to a specific moment for testing
        $testTime = Carbon::create(2024, 1, 1, 12, 0, 0);
        Carbon::setTestNow($testTime);

        // ---
        // Step 1: Issue a request to build 3 light fighters, 10 solar sats, and then 2 light fighters
        // ---
        $response = $this->post('/shipyard/add-buildrequest', [
            'token' => csrf_token(),
            'type' => '204', // Light fighter
            'amount' => 3,
            'planet_id' => $this->currentPlanetId,
        ]);
        $response = $this->post('/shipyard/add-buildrequest', [
            'token' => csrf_token(),
            'type' => '212', // Solar satellites
            'amount' => 10,
            'planet_id' => $this->currentPlanetId,
        ]);
        $response = $this->post('/shipyard/add-buildrequest', [
            'token' => csrf_token(),
            'type' => '204', // Light fighter
            'amount' => 2,
            'planet_id' => $this->currentPlanetId,
        ]);

        // Assert the response status is successful (302 redirect).
        $response->assertStatus(302);

        // ---
        // Step 2: Verify the ships are in the build queue
        // ---
        $response = $this->get('/shipyard');
        $response->assertStatus(200);
        $pattern = '/<span\s+class="level">\s*<span\s+class="textlabel">\s*Light\s*Fighter\s*<\/span>\s*0\s*<\/span>/';
        $result = preg_match($pattern, $response->getContent());
        $this->assertTrue($result === 1, 'Light Fighter is not at 0 units directly after build request issued.');
        $pattern = '/<span\s+class="level">\s*<span\s+class="textlabel">\s*Solar\s*Satellite\s*<\/span>\s*0\s*<\/span>/';
        $result = preg_match($pattern, $response->getContent());
        $this->assertTrue($result === 1, 'Solar Satellite is not at 0 units directly after build request issued.');

        // ---
        // Step 3: Verify that the light fighters and partial solar satellites are finished 30 minute later.
        // ---
        $testTime = Carbon::create(2024, 1, 1, 12, 25, 0);
        Carbon::setTestNow($testTime);

        $response = $this->get('/shipyard');
        $response->assertStatus(200);
        $pattern = '/<span\s+class="level">\s*<span\s+class="textlabel">\s*Light\s*Fighter\s*<\/span>\s*3\s*<\/span>/';
        $result = preg_match($pattern, $response->getContent());
        $this->assertTrue($result === 1, 'Light Fighter is not at 3 units 25m after build request issued.');
        $pattern = '/<span\s+class="level">\s*<span\s+class="textlabel">\s*Solar\s*Satellite\s*<\/span>\s*2\s*<\/span>/';
        $result = preg_match($pattern, $response->getContent());
        $this->assertTrue($result === 1, 'Solar Satellite is not at 2 units 25m after build request issued.');

        // ---
        // Step 5: Verify that ALL ships are finished 30 minute later.
        // ---
        $testTime = Carbon::create(2024, 1, 1, 14, 0, 0);
        Carbon::setTestNow($testTime);

        $response = $this->get('/shipyard');
        $response->assertStatus(200);
        $pattern = '/<span\s+class="level">\s*<span\s+class="textlabel">\s*Light\sFighter\s*<\/span>\s*5\s*<\/span>/';
        $result = preg_match($pattern, $response->getContent());
        $this->assertTrue($result === 1, 'Light Fighter build job is not finished 2h after build request issued.');
        $pattern = '/<span\s+class="level">\s*<span\s+class="textlabel">\s*Solar\s*Satellite\s*<\/span>\s*10\s*<\/span>/';
        $result = preg_match($pattern, $response->getContent());
        $this->assertTrue($result === 1, 'Solar Satellite build job is not finished 2h after build request issued.');
    }

    /**
     * Verify that building more than one of a defense unit works as expected.
     * @throws BindingResolutionException
     */
    public function testUnitQueueDefense(): void
    {
        // Add resources to planet that test requires.
        $this->planetAddResources(['metal' => 20000]);
        // Set the robotics factory to level 2
        $this->planetSetObjectLevel(14, 2, true);
        // Set shipyard to level 1.
        $this->planetSetObjectLevel(21, 1, true);

        // Set the current time to a specific moment for testing
        $testTime = Carbon::create(2024, 1, 1, 12, 0, 0);
        Carbon::setTestNow($testTime);

        // ---
        // Step 1: Issue a request to build 10 light fighters
        // ---
        $response = $this->post('/defense/add-buildrequest', [
            'token' => csrf_token(),
            'type' => '401', // Rocket launcher
            'amount' => 10,
            'planet_id' => $this->currentPlanetId,
        ]);
        $response->assertStatus(302);

        // ---
        // Step 2: Verify the defense units are in the build queue
        // ---
        $response = $this->get('/defense');
        $response->assertStatus(200);
        $pattern = '/<span\s+class="level">\s*<span\s+class="textlabel">\s*Rocket\s*Launcher\s*<\/span>\s*0\s*<\/span>/';
        $result = preg_match($pattern, $response->getContent());
        $this->assertTrue($result === 1, 'Rocket Launcher is not at 0 units directly after build request issued.');

        // ---
        // Step 3: Verify the defense units are still in the build queue 1 minute later.
        // ---
        $testTime = Carbon::create(2024, 1, 1, 12, 1, 0);
        Carbon::setTestNow($testTime);

        $response = $this->get('/defense');
        $response->assertStatus(200);
        $pattern = '/<span\s+class="level">\s*<span\s+class="textlabel">\s*Rocket\s*Launcher\s*<\/span>\s*0\s*<\/span>/';
        $result = preg_match($pattern, $response->getContent());
        $this->assertTrue($result === 1, 'Rocket Launcher is not at 0 units 30 seconds after build request issued.');

        // ---
        // Step 4: Verify that some defense units are finished 10 minute later.
        // ---
        $testTime = Carbon::create(2024, 1, 1, 12, 10, 0);
        Carbon::setTestNow($testTime);

        $response = $this->get('/defense');
        $response->assertStatus(200);
        $pattern = '/<span\s+class="level">\s*<span\s+class="textlabel">\s*Rocket\sLauncher\s*<\/span>\s*3\s*<\/span>/';
        $result = preg_match($pattern, $response->getContent());
        $this->assertTrue($result === 1, 'Rocket Launcher build job has not completed exactly 3 units 10 minutes after build request issued.');

        // ---
        // Step 5: Verify that ALL defense units are finished 1h later.
        // ---
        $testTime = Carbon::create(2024, 1, 1, 13, 0, 0);
        Carbon::setTestNow($testTime);

        $response = $this->get('/defense');
        $response->assertStatus(200);
        $pattern = '/<span\s+class="level">\s*<span\s+class="textlabel">\s*Rocket\sLauncher\s*<\/span>\s*10\s*<\/span>/';
        $result = preg_match($pattern, $response->getContent());
        $this->assertTrue($result === 1, 'Rocket Launcher build job is not finished yet 1h after build request issued.');
    }
}
