<?php

namespace XCOMDatabank\Front;

use XCOMDatabank\Covert\ActivityChain;
use XCOMDatabank\Covert\CovertAction;
use XCOMDatabank\Covert\CovertType;
use XCOMDatabank\Missions\Mission;
use XCOMDatabank\Utility\Database;

class ActivityChainPage
{
    private bool $isOngoingFirst;
    private bool $isCompletedFirst;

    public function __construct()
    {
        $this->isOngoingFirst = true;
        $this->isCompletedFirst = true;
    }

    public function getActivityChains(): void {
        $query = "SELECT * FROM xcom_activity_chain ORDER BY FIELD (status, 'Ongoing') DESC, end_date DESC";
        $params = array();
        $queryResult = Database::runQuery('select', $query, $params);

        while ($row = $queryResult->fetch()) {

            $newChain = new ActivityChain;
            $newChain->getActivityChain($row['id']);

            if($newChain->status == "Ongoing") {
                $statusClass = "alert-warning border-warning";
                $statusClassBox = "border-warning";
            } elseif($newChain->status == "Completed") {
                $statusClass = "alert-success border-success";
                $statusClassBox = "border-success";
            } elseif($newChain->status == "Abandoned") {
                $statusClass = "alert-secondary border-secondary";
                $statusClassBox = "border-secondary";
            } else {
                $statusClass = "alert-danger border-danger";
                $statusClassBox = "border-danger";
            }

            $idReplace = array(" ", ":", ".");
            $accordionID = str_replace($idReplace, "", $newChain->title);

            if($this->isCompletedFirst and $newChain->status != "Ongoing") {
                if(!$this->isOngoingFirst) {
                    echo '</div>';
                }
        ?>
                <div class="card page-header research-list-head">
                    <div class="card-header bg-success text-white">Completed Activity Chains</div>
                </div>
                <p><strong>Click Titles to Expand</strong></p>
                <div class="row">
        <?php
                $this->isCompletedFirst = false;
            }
            elseif($newChain->status == "Ongoing" and $this->isOngoingFirst) {
        ?>
                <div class="card page-header research-list-head">
                    <div class="card-header bg-success text-white">Ongoing Activity Chains</div>
                </div>
                <div class="row">
        <?php
                $this->isOngoingFirst = false;
            }
        ?>
                    <div class="col-12 chain-box-container accordion">
                        <div class="chain-box <?php echo $statusClassBox; ?> accordion-item">
                            <div class="accordion-header <?php echo $statusClass; ?>">
        <?php if($newChain->status == "Ongoing") { ?>
                                <button class="accordion-button <?php echo $statusClass; ?> border-bottom accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#<?php echo $accordionID; ?>" aria-expanded="true" aria-controls="<?php echo $accordionID; ?>"><?php echo $newChain->title; ?></button>
        <?php } else { ?>
                                <button class="accordion-button <?php echo $statusClass; ?> border-bottom accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#<?php echo $accordionID; ?>" aria-expanded="false" aria-controls="<?php echo $accordionID; ?>"><?php echo $newChain->title; ?></button>
        <?php } ?>
                            </div>
        <?php if($newChain->status == "Ongoing") { ?>
                            <div class="accordion-collapse collapse show" id="<?php echo $accordionID; ?>">
        <?php } else { ?>
                            <div class="accordion-collapse collapse" id="<?php echo $accordionID; ?>">
        <?php } ?>
                                <div class="flex-row d-flex gx-0 flex-wrap">
        <?php
            $newChainSteps = $newChain->chainSteps();
            foreach($newChainSteps as $step) {
                if($step->step > 1) {
        ?>
                                    <div class="chain-next-arrow text-success"><i class="fas fa-chevron-circle-right fa-4x"></i></div>
        <?php
                }
                if($step->type == "Covert") {
                    $getAction = new CovertAction;
                    $getAction->getCovertAction($step->covert);
                    $getType = new CovertType;
                    $getType->getCovertType($getAction->type);

                    if($getAction->status == "Complete" or $getAction->status == "Ambushed") {
                        $stepStatus = 'bg-success text-white';
                        $opStatus = 'bg-success text-white';
                        $opIcon = '<i class="fas fa-solid fa-check"></i>';
                    }
                    elseif($getAction->status == "In Progress") {
                        $stepStatus = 'bg-primary text-white';
                        $opStatus = 'bg-primary text-white';
                        $opIcon = '<i class="fas fa-cog fa-spin"></i>';
                    }
                    elseif($getAction->status == "Ambushed") {
                        $stepStatus = 'bg-success text-white';
                        $opStatus = 'bg-danger text-white';
                        $opIcon = '<i class="fas fa-solid fa-check"></i>';
                    }
                    else {
                        $stepStatus = '';
                        $opStatus = '';
                        $opIcon = '';
                    }
		?>

                                        <div class="chain-covert-box-container">
                                            <div class="card covert-box covert-<?php echo strtolower($getAction->faction); ?>">
                                                <div class="card-header"><?php echo $getType->name; ?></div>
                                                <ul class="list-group list-group-flush">
                                                    <li class="list-group-item covert-mission"><strong><?php echo $getType->mission; ?>:</strong><br /><?php echo $getAction->reward; ?></li>
                                                    <?php
                                                    if($getAction->status == "In Progress") {
                                                    ?>
                                                    <li class="list-group-item covert-status bg-primary text-white"><strong>Status:</strong> In Progress <i class="fas fa-cog fa-spin"></i></li>
                                                    <li class="list-group-item detail-list"><i class="far fa-calendar-alt fa-2x"></i><?php echo date('M d, Y', strtotime($getAction->startDate))." - TBD" ?></li>
                                                    <?php
                                                    } else {
                                                    ?>
                                                    <li class="list-group-item covert-status <?php echo $opStatus; ?>"><strong>Status: <?php echo $getAction->status; ?></strong></li>
                                                    <li class="list-group-item detail-list"><i class="far fa-calendar-alt fa-2x"></i><?php echo date('M d, Y', strtotime($getAction->startDate))." - ".date('M d, Y', strtotime($getAction->endDate)); ?></li>
                                                    <?php
                                                    }
                                                    ?>
                                                    <li class="list-group-item detail-list"><i class="fas fa-globe fa-2x text-success"></i><?php echo $getAction->location; ?></li>
                                                </ul>
                                            </div>
                                        </div>
        <?php
                }
                elseif($step->type == "Mission") {
				    $getMission = new Mission;
				    $getMission->getMission($step->mission);
				    $missionInfo = $getMission->missionInfo();
				    if($getMission->status == 4) {
        ?>
                                        <div class="chain-mission-box-container">
                                            <div class="card mission-box mission-infiltrating border-primary">
                                                <div class="card-header bg-primary text-white">
                                                    <strong>Operation <?php echo $getMission->operationName; ?></strong>
                                                </div>
                                                <ul class="list-group list-group-flush">
                                                    <li class="list-group-item"><strong>Infiltrating <i class="fas fa-cog fa-spin text-primary"></i></strong></li>
                                                    <li class="list-group-item operation"><strong><?php echo $missionInfo['type']; ?></strong><br />
                                                    <?php echo $missionInfo['objective']; ?></li>
                                                    <li class="list-group-item mission-location detail-list"><i class="fas fa-globe fa-2x"></i><?php echo $getMission->sector; ?></li>
                                                </ul>
                                            </div>
                                        </div>
        <?php

				    }
                    else {
                        $missionComplete = $getMission->status == 1 ? "mission-completed" : '';
                        $missionComplete = $getMission->status == 2 ? "mission-failed" : $missionComplete;
                        $missionComplete = $getMission->status == 3 ? "objective-completed" : $missionComplete;

                        if($getMission->is_infiltration) {
                            $missionTypeStyle = "text-primary";
                            $missionType = "Infiltration";
                            $missionTypeIcon = "fa-cog";
                        } else {
                            $missionTypeStyle = "text-danger";
                            $missionType = "Assault";
                            $missionTypeIcon = "fa-crosshairs";
                        }

                        $missionRating = $getMission->rating == "Flawless" ? "bg-success text-white" : '';
                        $missionRating = $getMission->rating == "Excellent" ? "bg-info text-white" : $missionRating;
                        $missionRating = $getMission->rating == "Good" ? "bg-secondary text-white" : $missionRating;
                        $missionRating = $getMission->rating == "Fair" ? "bg-warning text-white" : $missionRating;
                        $missionRating = $getMission->rating == "Poor" ? "bg-danger text-white" : $missionRating;
        ?>
                                        <div class="chain-mission-box-container rating-<?php echo strtolower($getMission->rating); ?>">
                                            <div class="card mission-box <?php echo $missionComplete; ?>">
                                                <div class="card-header">
                                                    <strong><a href="<?php echo "/mission/".strtolower(str_replace(" ","~",$getMission->operationName))."/"; ?>">Operation <?php echo $getMission->operationName; ?></a></strong>
                                                </div>
                                                <ul class="list-group list-group-flush">
                                                    <li class="list-group-item <?php echo $missionTypeStyle; ?>"><strong><?php echo $missionType; ?> Mission <i class="fas <?php echo $missionTypeIcon; ?>"></i></strong></li>
                                                    <li class="list-group-item operation"><strong><?php echo $missionInfo['type']; ?></strong><br />
                                                    <?php echo $missionInfo['objective']; ?></li>
                                                    <li class="list-group-item episodes detail-list"><i class="fab fa-youtube fa-2x"></i><?php
                                                        for ($x = 0; $x < sizeof($getMission->episode); $x++) {
                                                            if($x > 0) {
                                                                echo " | ";
                                                            }
                                                            echo '<a href="'.$getMission->url[$x].'" target="_blank">Episode '.$getMission->episode[$x].'</a>';
                                                        }
                                                    ?>
                                                    </li>
                                                </ul>
                                                <ul class="list-group list-group-flush">
                                                    <li class="list-group-item mission-date detail-list"><i class="far fa-calendar-alt fa-2x"></i><?php echo date('F d, Y', strtotime($getMission->missionDate)); ?></li>
                                                    <li class="list-group-item mission-location detail-list"><i class="fas fa-globe fa-2x"></i><?php echo $getMission->sector; ?></li>
                                                    <li class="list-group-item mission-objective <?php echo $missionRating; ?>"><strong>Mission Rating: <?php echo $getMission->rating; ?></strong></li>
                                                </ul>
                                            </div>
                                        </div>
        <?php
                    }
                }
            }
        ?>
                                    </div>
                                </div>
                            </div>
                        </div>
        <?php
        }
    }
}