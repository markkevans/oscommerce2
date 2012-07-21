<?php

use Behat\Behat\Context\ClosuredContextInterface,
    Behat\Behat\Context\TranslatedContextInterface,
    Behat\Behat\Exception\PendingException;

use Behat\MinkExtension\Context\MinkContext;
use Behat\Behat\Context\BehatContext,
    Behat\Behat\Event\SuiteEvent,
    Behat\Behat\Event\ScenarioEvent;

use Behat\Gherkin\Node\PyStringNode,
    Behat\Gherkin\Node\TableNode;
use Phabric\Factory as pFactory;
use Phabric\Phabric;

require_once 'PHPUnit/Autoload.php';
require_once 'PHPUnit/Framework/Assert/Functions.php';

$phaLoader = new \Doctrine\Common\ClassLoader('Phabric', realpath(__DIR__ . '/../../../lib/'));
$phaLoader->register();

$docLoader = new \Doctrine\Common\ClassLoader('Doctrine\DBAL', __DIR__ . '/../../../lib/Vendor/Doctrine/lib');
$docLoader->register();

$docComLoader = new \Doctrine\Common\ClassLoader('Doctrine\Common', __DIR__ . '/../../../lib/Vendor/Doctrine/lib/vendor/doctrine-common/lib');
$docComLoader->register();

/**
 * Features context.
 */
class FeatureContext extends MinkContext
{
	
    /**
     * The Phabric Bus
     *
     * @var Phabric\Bus
     */
    private $phabric;

    /**
     * The Databse Connection.
     *
     * @var Doctrine\DBAL\Connection
     */
    private static $db;

    /**
     * Initializes context.
     * Every scenario gets it's own context object.
     *
     * @param   array   $parameters     context parameters (set them up through behat.yml)
     */
    public function __construct(array $parameters)
    {
        $config = new \Doctrine\DBAL\Configuration();

        self::$db = \Doctrine\DBAL\DriverManager::getConnection(array(
                    'dbname' => $parameters['database']['dbname'],
                    'user' => $parameters['database']['username'],
                    'password' => $parameters['database']['password'],
                    'host' => $parameters['database']['host'],
                    'driver' => $parameters['database']['driver'],
                ));
 
        $datasource = new \Phabric\Datasource\Doctrine(self::$db, $parameters['Phabric']['entities']);
        $this->phabric = new Phabric($datasource);
        $this->phabric->createEntitiesFromConfig($parameters['Phabric']['entities']);		
    }

	/**
	 * @BeforeSuite
	 */
	public static function prepare(SuiteEvent $event)
	{
       $sql = file_get_contents(__DIR__ . '/../../config/fixtures/reset.sql');
        self::$db->query($sql);
	}

	/**
     * @Given /^The following customers exist$/
     */
    public function theFollowingCustomersExist(TableNode $table) {
        $this->phabric->insertFromTable('customers', $table);
    }
	
	/**
     * @When /^I select all records from the customers table$/
     */
    public function iSelectAllRecordsFromTheCustomersTable() {
        $sql = 'SELECT * FROM customers';

        $rows = self::$db->fetchAll($sql);
        
        $this->qResult = $rows;
    }
	
	/**
     * @Then /^I should see the following records$/
     */
    public function iShouldSeeTheFollowingRecords(TableNode $table) {
        
        // Get the col names
        $topRow = reset($this->qResult);

        // Col names - id
        $cols = array_keys($topRow);
        array_shift($cols);

        $actualResults = array($cols);

        foreach ($this->qResult as $row) {
            // Remove the id from the results
            array_shift($row);
            $actualResults[] = array_values($row);
        }

        $expectedResults = $table->getRows();
        assertEquals($expectedResults, $actualResults);
    }
}