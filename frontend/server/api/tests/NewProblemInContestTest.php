<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

require_once '../NewProblemInContest.php';

require_once 'Utils.php';

class NewProblemInContestTest extends PHPUnit_Framework_TestCase
{
    
    public function setUp()
    {        
        Utils::ConnectToDB();
    }
    
    private static function setValidContext()
    {        
        // Set context
        $_GET["contest_id"] = Utils::GetValidPublicContestId();
        $_POST["title"] = Utils::CreateRandomString();
        $_POST["alias"] = substr(Utils::CreateRandomString(), 0, 10);
        $_POST["author_id"] = Utils::GetContestantUserId();
        $_POST["validator"] = "token";
        $_POST["time_limit"] = 5000;
        $_POST["memory_limit"] = 32000;        
        $_POST["source"] = "<p>redacción</p>";
        $_POST["order"] = "normal";
        $_POST["points"] = 1;
    }
    
    public function testCreateValidProblem()
    {
        // Login as judge
        $auth_token = Utils::LoginAsJudge();        
        
        self::setValidContext();
        
        // Execute API
        Utils::SetAuthToken($auth_token);
        $newProblemInContest = new NewProblemInContest();
        
        try
        {
            $return_array = $newProblemInContest->ExecuteApi();
        }
        catch(ApiException $e)
        {
            var_dump($e->getArrayMessage());
            $this->fail("Unexpected exception");
        }
        
        // Verify status
        $this->assertEquals("ok", $return_array["status"]);
        
        // Verify data in DB
        $problems = ProblemsDAO::getAll();
        $problem = null;
        foreach($problems as $p)
        {
            if($p->getTitle() === $_POST["title"])
            {
                $problem = $p;
                break;
            }
        }
        
        // Verify contest was found
        $this->assertNotNull($problem);
        
        // Verify DB data
        $this->assertEquals($_POST["title"], $problem->getTitle());
        $this->assertEquals($_POST["alias"], $problem->getAlias());
        $this->assertEquals($_POST["validator"], $problem->getValidator());
        $this->assertEquals($_POST["time_limit"], $problem->getTimeLimit());
        $this->assertEquals($_POST["memory_limit"], $problem->getMemoryLimit());      
        $this->assertEquals($_POST["source"], $problem->getSource());
        $this->assertEquals($_POST["author_id"], $problem->getAuthorId());
        
        // Default data
        $this->assertEquals(0, $problem->getVisits());
        $this->assertEquals(0, $problem->getSubmissions());
        $this->assertEquals(0, $problem->getAccepted());
        $this->assertEquals(0, $problem->getDifficulty());       
        
        // Get problem-contest and verify it
        $contest_problems = ContestProblemsDAO::getByPK(Utils::GetValidPublicContestId(), $problem->getProblemId());
        $this->assertNotNull($contest_problems);        
        $this->assertEquals(1, $contest_problems->getPoints());        
    }
        
    
    public function testCreateProblemAsContestant()
    {
        // Login as judge
        $auth_token = Utils::LoginAsContestant();
        
        // Set context
        self::setValidContext();
        
        // Execute API
        Utils::SetAuthToken($auth_token);
        $newProblemInContest = new NewProblemInContest();
        
        try
        {
            $return_array = $newProblemInContest->ExecuteApi();
        }
        catch(ApiException $e)
        {            
            // Validate error
            $exception_message = $e->getArrayMessage();            
            $this->assertEquals("User is not allowed to view this content.", $exception_message["error"]);
            $this->assertEquals("error", $exception_message["status"]);
            $this->assertEquals("HTTP/1.1 403 FORBIDDEN", $exception_message["header"]);
            
            // We're OK
            return;
        }
                
        var_dump($return_array);
        $this->fail("Contestant was able to create contest");                
    }
    
    
    public function testRequiredParameters()
    {
        // Login as judge
        $auth_token = Utils::LoginAsJudge();
        
        // Set valid context
        self::setValidContext();
        
        // Array of valid keys
        $valid_keys = array(
            "title",
            "alias",
            "validator",
            "time_limit",            
            "memory_limit",
            "source",
            "author_id",            
        );
        
        foreach($valid_keys as $key)        
        {        
            // Set auth key
            Utils::SetAuthToken($auth_token);
            $newProblem = new NewProblemInContest();
            
            // Unset key
            unset($_POST[$key]);
            
            try
            {
                // Execute API
                $newProblem->ExecuteApi();
                
            }
            catch(ApiException $e)
            {
                // Exception is expected
                $exception_array = $e->getArrayMessage();            

                // Validate exception
                $this->assertNotNull($exception_array);
                $this->assertArrayHasKey('error', $exception_array);    

                if ($key !== "start_time" )
                {
                    $this->assertEquals("Required parameter ". $key ." is missing.", $exception_array["error"]);
                }

                return;
            }

            $this->fail("Exception was expected. Parameter: ". $key);            
        }   
        
        
    }
}
?>
