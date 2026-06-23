<?php
declare(strict_types = 1);

namespace XCOMDatabank\Front;

use XCOMDatabank\Covert\ActivityChain;
use XCOMDatabank\Covert\CovertAction;
use XCOMDatabank\Covert\CovertType;
use XCOMDatabank\Management\Info;
use XCOMDatabank\Management\Research;
use XCOMDatabank\Missions\Mission;
use XCOMDatabank\Utility\Database;

class Homepage
{
    function __construct() {

    }

    public Function getHomepage(): void {
        ?>
        <div class="row equal-height justify-content-between">
            <div class="col-12 col-sm-5 col-md-4 col-lg-auto homepage-box">
                <?php self::getDateBox(); ?>
            </div>
            <div class="col-12 col-sm-7 col-md-8 col-lg-auto homepage-box">
                <?php self::getAvatarBox(); ?>
            </div>
            <div class="col-12 col-sm-8 col-lg-auto homepage-box">
                <?php self::getResearchBox(); ?>
            </div>
            <div class="col-12 col-sm-4 col-lg-auto homepage-box">
                <?php self::getCrewBox(); ?>
            </div>
            <div class="col-12 col-sm-4 col-lg-auto homepage-box">
                <?php self::getFLBox(); ?>
            </div>
        </div>
        <div class="row">
            <div class="col-12 homepage-box">
                <?php self::getMissionBox(); ?>
            </div>
            <div class="col-12 col-lg-6 homepage-box">
                <?php self::getActivityChains(); ?>
            </div>
            <div class="col-12 col-lg-6 homepage-box">
                <?php self::getDarkEvents(); ?>
            </div>
        </div>
        <?php
    }

    private static function getDateBox(): void {
        ?>
                <div class="card border-success">
                    <div class="card-header alert-success border-success">Current Date <i class="fa-solid fa-calendar-days text-success"></i></div>
                    <div class="card-body homepage-info-box"><?php echo Info::getCurrentDate(); ?></div>
                </div>
        <?php
    }

    private static function getAvatarBox(): void {
        ?>
                <div class="card border-danger">
                    <div class="card-header alert-danger border-danger">Avatar Project Progress <i class="fa-solid fa-dna text-danger"></i></div>
                    <div class="card-body homepage-info-box avatar-boxes">
                        <?php echo Info::getAvatarPips(); ?>
                    </div>
                </div>
        <?php
    }

    private static function getResearchBox(): void {
        ?>
                <div class="card border-primary">
                    <div class="card-header alert-primary border-primary">Current Research <i class="fa-solid fa-flask text-primary"></i></div>
                    <div class="card-body homepage-info-box"><?php echo Research::getCurrentResearch(); ?></div>
                </div>
        <?php
    }

    private static Function getCrewBox(): void {
        ?>
                <div class="card border-warning">
                    <div class="card-header alert-warning border-warning">Crew <i class="fa-solid fa-users text-secondary"></i></div>
                    <div class="card-body homepage-info-box"><?php echo Info::getCrew(); ?></div>
                </div>
        <?php
    }

    private static Function getFLBox(): void {
        ?>
        <div class="card border-purple">
            <div class="card-header alert-purple border-purple">FL <i class="fa-solid fa-hand-fist"></i></div>
            <div class="card-body homepage-info-box"><?php echo Info::getForceLevel(); ?> / 20</div>
        </div>
        <?php
    }

    private static function getMissionBox(): void {
        $getMission = new Mission;

        $query = "SELECT id FROM xcom_mission WHERE status != 4 ORDER BY mission_date desc, episode desc, id DESC LIMIT 1";
        $params = array();
        $queryResult = Database::runQuery('select', $query, $params);

        $row = $queryResult->fetch();
        $getMission->getMission(intval($row['id']));
        $getMissionInfo = $getMission->missionInfo();

        if($getMission->is_infiltration) {
            $missionType = "Infiltration";
            $missionTypeStyle = "text-primary";
            $missionTypeIcon = "gear";
        } else {
            $missionType = "Assault";
            $missionTypeStyle = "text-danger";
            $missionTypeIcon = "crosshairs";
        }
        ?>
                <div class="row gx-0 border border-secondary rounded">
                    <div class="col-12 col-sm-6 bg-light homepage-mission-info">
                        <div class="homepage-mission-name bg-light border-bottom border-dark">
                            <a href="<?php echo "/mission/".strtolower(str_replace(" ","~",$getMission->operationName))."/"; ?>">Operation <?php echo $getMission->operationName; ?></a>
                        </div>
                        <div class="homepage-mission-objective">
                            <strong><?php echo $getMissionInfo['type']; ?></strong> <i class="fa-solid fa-circle-chevron-right text-success fa-2x"></i> <strong><?php echo $getMissionInfo['objective']; ?></strong>
                        </div>
    <?php
        if($getMission->status == 1) { ?>
                        <div class="homepage-mission-result text-success"><i class="fa-solid fa-square-check fa-2x"></i> Success</div>
    <?php
        } else { ?>
                        <div class="homepage-mission-result text-danger"><i class="fa-solid fa-square-xmark fa-2x"></i> Failed</div>
    <?php	} ?>
                        <div class="<?php echo $missionTypeStyle; ?> homepage-mission-type"><i class="fa-solid fa-<?php echo $missionTypeIcon; ?> fa-2x"></i> <?php echo $missionType; ?></div>
                        <div class="homepage-mission-date"><i class="fa-solid fa-calendar-days fa-2x"></i><?php echo date('F d, Y', strtotime($getMission->missionDate)); ?></div>
                        <div class="homepage-mission-location"><i class="fa-solid fa-globe fa-2x text-primary"></i> <?php echo $getMission->location.' ('.$getMission->sector.')'; ?></div>
                        <div class="homepage-mission-url"><i class="fa-brands fa-youtube fa-2x"></i>
                            <?php
                            for ($x = 0; $x < sizeof($getMission->episode); $x++) {
                                if($x > 0) {
                                    echo " | ";
                                } ?>
                                <a href="<?php echo $getMission->url[$x]; ?>.'" target="_blank">Watch Episode <?php echo $getMission->episode[$x]; ?></a>
                                <?php
                            }
                            ?>
                        </div>
                    </div>
                    <div class="col-12 col-sm-6 bg-light homepage-mission-picture">
                        <img src="<?php echo $getMission->picture; ?>" alt="Operation <?php echo $getMission->operationName; ?>" class="border border-top-0 border-secondary" />
                    </div>
                </div>
        <?php
    }

    private static function getActivityChains(): void {
        $query = "SELECT id FROM xcom_activity_chain WHERE status = 'Ongoing' ORDER BY 'start_date' DESC";
        $params = array();
        $queryResult = Database::runQuery('select', $query, $params);

        $activeChainCount = $queryResult->rowCount();
        if($activeChainCount == 0) {
            $chainCount = 1;
        } else {
            $chainCount = $activeChainCount;
        }
        ?>
                <div class="homepage-chain-header text-primary">
                    Ongoing Activity Chains <i class="fa-solid fa-link"></i>
                </div>
                <div class="chain-carousel border border-dark rounded">
                    <div id="homepage-chain-carousel" class="carousel carousel-dark slide" data-bs-ride="carousel">
                        <div class="carousel-indicators">
        <?php
		$j = 0;
		for($i = $chainCount; $i > 0; $i--) {
			$j = $j + 1;
        ?>
                            <button <?php if($j == 1) { ?>class="active" <?php } ?>type="button" data-bs-target="#homepage-chain-carousel" data-bs-slide-to="<?php echo $j - 1; ?>" aria-label="Slide <?php echo $j; ?>"></button>
        <?php
        }
        ?>
                        </div>
                        <div class="carousel-inner">
        <?php
        $isFirstChain = true;
        while ($row = $queryResult->fetch()) {

            $resultsArray = [];

            $thisChain = new ActivityChain;
            $thisChain->getActivityChain((int)$row['id']);

            // Set Activity Chain Title
            $resultsArray['title'] = $thisChain->title;

            // Get the last step in the current chain
            $thisChainSteps = $thisChain->chainSteps();
            $getLatestStep = end($thisChainSteps);

            // Set Title Status (Current vs. Most Recent) and associated icon
            if($getLatestStep->status == "Ongoing") {
                $resultsArray['title-class'] = "text-primary";
                $resultsArray['title-status'] = "Current Objective";
                $resultsArray['title-icon'] = "fa-cog fa-spin";
            } else {
                $resultsArray['title-class'] = "text-success";
                $resultsArray['title-status'] = "Most Recent Objective";
                $resultsArray['title-icon'] = "fa-check-circle";
            }

            // If step is a Covert Action, get covert action info
            if($getLatestStep->type == "Covert") {
                $getCovert = new CovertAction;
                $getCovert->getCovertAction($getLatestStep->covert);
                $getType = new CovertType;
                $getType->getCovertType($getCovert->type);

                // Set faction class. In this case "homepage-covert-{faction name}"
                $resultsArray['step-class'] = "homepage-covert-".strtolower($getCovert->faction);

                // Set Step Title, in this case, the Covert Action Type
                $resultsArray['step-title'] = $getType->name;

                // Set Step Type, In this case, the Covert Action Title
                $resultsArray['step-type'] = $getType->mission;

                // Set Step Objective, In this case, the Covert Action Rewards
                $resultsArray['step-objective'] = $getCovert->reward;

                // Checks if Covert Action is In Progress. If so, only list start date. Else, list both start and end date. Either way, has date is true
                $resultsArray['step-has-date'] = true;
                if($getCovert->status == "In Progress") {
                    $resultsArray['step-date'] = $getCovert->startDate." - TBD";
                } else {
                    $resultsArray['step-date'] = $getCovert->startDate." - ".$getCovert->endDate;
                }

                // Sets Covert Action Location
                $resultsArray['step-location'] = $getCovert->location;
            }
            // If step is a Mission, get Mission info
            elseif($getLatestStep->type == "Mission") {
                $getMission = new Mission;
                $getMission->getMission($getLatestStep->mission);

                // Set div class. Only item here (and I'm not even sure it's relevant) is Infiltration vs. Not
                // Can also set date fields here too, since that is dependent on an is_infiltration check
                if($getLatestStep->status == "Ongoing") {
                    $resultsArray['step-class'] = "homepage-mission-infiltration";
                    $resultsArray['step-has-date'] = false;
                    $resultsArray['step-date'] = "";
                } else {
                    $resultsArray['step-class'] = "homepage-mission-assault";
                    $resultsArray['step-has-date'] = true;
                    $resultsArray['step-date'] = $getMission->missionDate;
                }

                // Set Step Title. In this case, the operation name
                $resultsArray['step-title'] = "Operation ".$getMission->operationName;

                // Get mission info for next two keys
                $missionStepInfo = $getMission->missionInfo();

                // Set Step Type. In this case, the Mission type
                $resultsArray['step-type'] = $missionStepInfo['type'];

                // Set Step Objective. In this case, the Mission objective
                $resultsArray['step-objective'] = $missionStepInfo['objective'];

                // Set Step Location
                $resultsArray['step-location'] = $getMission->sector;
            }
        ?>
                                <div class="<?php if($isFirstChain) { echo "active "; } ?>carousel-item" data-bs-interval="10000">
                                    <div class="card card-carousel">
                                        <div class="card-header alert-info border-dark"><?php echo $resultsArray['title']; ?></div>
                                        <div class="card-body">
                                            <div class="card-title border-bottom border-primary <?php echo $resultsArray['title-class']; ?>"><?php echo $resultsArray['title-status']; ?> <i class="fas <?php echo $resultsArray['title-icon']; ?>"></i></div>
                                            <div class="<?php echo $resultsArray['step-class']; ?>">
                                                <div class="card-text homepage-step-title"><?php echo $resultsArray['step-title']; ?></div>
                                                <div class="card-text homepage-step-objective"><strong><?php echo $resultsArray['step-type']; ?></strong><br />
                                                    <?php echo $resultsArray['step-objective']; ?></div>
                                                <?php
                                                if($resultsArray['step-has-date']) { ?>
                                                    <div class="card-text homepage-step-date"><i class="far fa-calendar-alt"></i> <?php echo $resultsArray['step-date']; ?></div>
                                                    <?php
                                                } ?>
                                                <div class="card-text homepage-step-location"><i class="fas fa-globe text-success"></i> <?php echo $resultsArray['step-location']; ?></div>
                                            </div>
                                        </div>
                                        <div class="card-footer"></div>
                                    </div>
                                </div>
        <?php
            // End Carousel Item Loop
            $isFirstChain = false;
        }
        // In case there are no results from the query
        if($activeChainCount == 0) {
        ?>
                                <div class="active carousel-item" data-bs-interval="10000">
                                    <div class="card card-carousel">
                                        <div class="card-header alert-warning border-dark">No Activity Chains</div>
                                        <div class="card-body">
                                            <h3 class="text-danger">There are no active Activity Chains</h3>
                                        </div>
                                        <div class="card-footer"></div>
                                    </div>
                                </div>
        <?php
        }
        ?>
                        </div>
                        <button class="carousel-control-prev" type="button" data-bs-target="#homepage-chain-carousel" data-bs-slide="prev">
                            <span class="carousel-control-prev-icon" aria-hidden="true"></span>
                            <span class="visually-hidden">Previous</span>
                        </button>
                        <button class="carousel-control-next" type="button" data-bs-target="#homepage-chain-carousel" data-bs-slide="next">
                            <span class="carousel-control-next-icon" aria-hidden="true"></span>
                            <span class="visually-hidden">Next</span>
                        </button>
                    </div>
                </div>
        <?php
    }

    private static function getDarkEvents(): void {
        $query = "SELECT name FROM xcom_dark_event WHERE enabled = true and active = true ORDER BY name";
        $params = array();
        $queryResult = Database::runQuery('select', $query, $params);

        if($queryResult->rowCount() > 0) {
        ?>
                <div class="homepage-event-header text-danger">
                    Active Dark Events <i class="fa-solid fa-bahai"></i>
                </div>
                <div class="events-box bg-white border border-danger rounded">
        <?php
            if ($queryResult->rowCount() == 0) { ?>
                            <p><strong>None</strong></p>
        <?php } else {
                foreach($queryResult as $row) {
        ?>
                            <p class="text-danger"><strong><?php echo $row['name']; ?></strong></p>
        <?php
                }
            }
        ?>
                </div>
        <?php
        }
    }
}