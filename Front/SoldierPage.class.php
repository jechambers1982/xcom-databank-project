<?php
declare(strict_types = 1);

namespace XCOMDatabank\Front;

use PDO;
use XCOMDatabank\Covert\CovertAction;
use XCOMDatabank\Covert\CovertOperative;
use XCOMDatabank\Covert\CovertType;
use XCOMDatabank\Missions\Mission;
use XCOMDatabank\Missions\MissionSoldier;
use XCOMDatabank\Soldiers\Soldier;
use XCOMDatabank\Utility\Database;

class SoldierPage
{
    public string $type;
    public ?int $id;
    public ?string $name;

    function __construct(string $type, ?int $id, ?string $name) {
        $this->type = $type;
        $this->id = $id;
        $this->name = $name;
    }

    public function getSoldierPage(): void {
        // If variable in URL is ID, select by that. If name, search by that, otherwise, error
        if($this->type === "id" and isset($this->id)) {
            $query = "SELECT * from xcom_soldier WHERE id = :id";
            $params[0] = array("param" => ":id", "var" => $this->id, "type" => PDO::PARAM_INT,);
        }
        elseif($this->type === "name" and isset($this->name)) {
            $name = explode("~",$this->name);
            if(isset($name[0]) and isset($name[1])) {
                $fnspace = str_replace("-", " ", $name[0]);
                $lnspace = str_replace("-", " ", $name[1]);
                $query = 'SELECT * from xcom_soldier WHERE (last_name = :lastname or last_name = :lastnamespace) and (first_name = :firstname or first_name = :firstnamespace)';
                $params[0] = array("param" => ":lastname", "var" => $name[1], "type" => PDO::PARAM_STR,);
                $params[1] = array("param" => ":firstname", "var" => $name[0], "type" => PDO::PARAM_STR,);
                $params[2] = array("param" => ":lastnamespace", "var" => $lnspace, "type" => PDO::PARAM_STR,);
                $params[3] = array("param" => ":firstnamespace", "var" => $fnspace, "type" => PDO::PARAM_STR,);
            }
            else {
                echo '<h2>Perhaps there was a sale on ADVENT burgers?</h2>';
                echo "<p>Sorry, this soldier doesn't appear to exist.</p>";
                return;
            }
        }
        else {
            echo '<h2>Perhaps there was a sale on ADVENT burgers?</h2>';
            echo "<p>Sorry, this soldier doesn't appear to exist.</p>";
            return;
        }
        $queryResult = Database::runQuery('select', $query, $params);
        $count = $queryResult->rowCount();

        if($count == 0) {
            echo '<h2>Perhaps there was a sale on ADVENT burgers?</h2>';
            echo "<p>Sorry, this soldier doesn't appear to exist.</p>";
            exit;
        }
        else {
            $result = $queryResult->fetch();
            $id = $result['id'];
        }

        // Create soldier object, and get soldier data
        $soldier = new Soldier();
        $soldier->getSoldier(intval($id));
        $soldierInfo = $this->getSoldierInfo($soldier);

        // Get Bond, if applicable
        $bondInfo = $this->getBondInfo($soldier->id);

        // Get Mission Data
        $missionInfo = $this->getMissionInfo($soldier->id);
        ?>

        <div class="row equal-height mb-5">
        <?php
        if($soldier->killed == 1) { ?>
            <div class="col-12 soldier-name bg-danger text-white"><?php echo $soldierInfo['soldierName']; ?> (Killed)</div>
        <?php	}
        elseif($soldier->killed == 2) { ?>
            <div class="col-12 soldier-name bg-warning text-white"><?php echo $soldierInfo['soldierName']; ?> (Captured)</div>
        <?php
        }
        else { ?>
            <div class="col-12 soldier-name bg-success text-white"><?php echo $soldierInfo['soldierName']; ?></div>
        <?php	} ?>
            <div class="col-12 col-md-3 px-0 soldier-image"><img src="<?php echo $soldier->photo; ?>" alt="<?php echo $soldierInfo['soldierAltName']; ?>" /></div>
            <div class="col-12 col-md-3 py-2 soldier-info"><?php $this->getBasicInfo($soldierInfo, $bondInfo); ?></div>
            <div class="col-12 col-md-3 soldier-stats"><?php $this->getSoldierAttributes($soldier, $soldierInfo['class']); ?></div>
            <div class="col-12 col-md-3 soldier-stats-list"><?php $this->getMissionStats($missionInfo['missionCount'], $missionInfo['covertCount'], $soldierInfo['stats']) ?></div>
        </div>
    <?php

        // Soldier Skills
        $this->getSoldierSkills($soldierInfo['skills']);

        // Mission List
        $this->getSoldierMissions($missionInfo['missionResult'], $missionInfo['covertResult'], $missionInfo['totalCount'], $soldierInfo['rank'], $soldierInfo['class']);

    }

    private function getBasicInfo(array $soldierInfo, array $bondInfo): void {
        ?>
        <div class="row">
            <div class="col-12 country-flag">
                <img src="/img/flags/<?php echo $soldierInfo['flag']; ?>.png" alt="<?php echo $soldierInfo['country']; ?>" height="50" />
                <span class="country-name"><?php echo $soldierInfo['country']; ?></span>
            </div>
            <div class="col-12 rank-icon py-2">
                <img src="<?php echo $soldierInfo['rank']['icon']; ?>" alt="<?php echo $soldierInfo['rank']['name']; ?>" height="50" width="50" />
                <span><?php echo $soldierInfo['rank']['name']; ?></span>
            </div>
            <div class="col-12 class-icon py-2">
                <img src="<?php echo $soldierInfo['class']['icon']; ?>" alt="<?php echo $soldierInfo['class']['name']; ?>" height="50" width="50" />
                <span><?php echo $soldierInfo['class']['name']; ?></span>
            </div>
        <?php
        if(sizeof($bondInfo) == 3) { ?>
            <div class="col-12 bond-icon py-2">
                <img src="/img/icons/bonds/bond<?php echo $bondInfo['level']; ?>.png" alt="Bond Level <?php echo $bondInfo['level']; ?>" height="50" />
                <span class="card-text"><a href="<?php echo $bondInfo['link']; ?>"><?php echo $bondInfo['name']; ?></a></span>
            </div>
        <?php	} ?>
        </div>
        <?php
    }

    private function getMissionStats(int $missionCount, int $covertCount, array $statsInfo): void {
        ?>
        <div class="row">
            <div class="col-12 soldier-page-total-stats total-missions"><strong>Total Missions:</strong> <?php echo $missionCount; ?></div>
            <div class="col-12 soldier-page-total-stats total-covert"><strong>Covert Actions:</strong> <?php echo $covertCount; ?></div>
            <div class="col-12 soldier-page-total-stats damage-dealt"><strong>Damage Dealt:</strong> <?php echo $statsInfo['damage']; ?></div>
            <div class="col-12 soldier-page-total-stats aliens-killed"><strong>Aliens Killed:</strong> <?php echo $statsInfo['aliens_killed']; ?></div>
            <div class="col-12 soldier-page-total-stats lost-killed"><strong>Lost Killed:</strong> <?php echo $statsInfo['lost_killed']; ?></div>
            <div class="col-12 soldier-page-total-stats shot-pct"><strong>Shot Percentage:</strong> <?php echo $statsInfo['shots_pct']."% (".$statsInfo['shots_hit']."/".$statsInfo['shots_taken'].")"; ?></div>
            <div class="col-12 soldier-page-total-stats total-eas"><strong>EAS:</strong> <?php echo number_format($statsInfo['eas'] ?? 0,2); ?></div>
            <div class="col-12 soldier-page-total-stats date-joined"><strong>Date Joined:</strong> <?php echo date('F d, Y', strtotime($statsInfo['dateJoined'])) ?? "No Date"; ?></div>
        </div>
        <?php
    }

    private function getSoldierAttributes(Soldier $soldier, array $classInfo): void {
        ?>
            <div class="row">
                <div class="col-6 stat-aim stat-icon"><?php echo $soldier->aim; ?></div>
                <div class="col-6 stat-hack stat-icon"><?php echo $soldier->hack; ?></div>
                <div class="col-6 stat-health stat-icon"><?php echo $soldier->health; ?></div>
                <div class="col-6 stat-dodge stat-icon"><?php echo $soldier->dodge; ?></div>
                <div class="col-6 stat-movement stat-icon"><?php echo $soldier->movement; ?></div>
                <?php if ($soldier->psi == null) { ?>
                <div class="col-6 stat-icon"></div>
                <?php } else { ?>
                <div class="col-6 stat-psi stat-icon"><?php echo $soldier->psi; ?></div>
                <?php }
                if($soldier->will == null) { ?>
                <div class="col-6 stat-icon"></div>
                <?php } else { ?>
                <div class="col-6 stat-will stat-icon"><?php echo $soldier->will; ?></div>
                <?php } ?>
                <div class="col-6 stat-icon"></div>
                <?php if ($soldier->intelligence == null) { ?>
                <div class="col-12 stat-icon"></div>
                <?php } else { ?>
                <div class="col-12 stat-intelligence stat-icon"><?php echo $soldier->intelligence; ?></div>
                <?php } ?>
            </div>
        <?php
    }

    private function getSoldierSkills(array $skillsInfo): void {
        ?>
        <div class="row mb-5">
            <div class="col-sm-12 px-0">
                <div class="card skills-card">
                    <div class="card-header skills-header">Soldier Skills</div>
                    <div class="row">
        <?php
		$columnCount = 0;
		foreach($skillsInfo as $key => $value) {
        ?>
                        <div class="col-sm-12 col-lg-3">
                            <div class="card-body skill-icon <?php echo str_replace(" ","-",strtolower($key)); ?>">
                                <img src="<?php echo $value; ?>" alt="<?php echo $key; ?>" height="32" width="32" />
                                <span class="card-text"><?php echo $key; ?></span>
                            </div>
                        </div>
        <?php
			$columnCount = $columnCount + 1;
			if($columnCount == 4) {
				$columnCount = 0;
			}
		}
		if($columnCount == 3) {
        ?>
						<div class="col-sm-12 col-lg-9 d-sm-none">
                            <div class="card-body"></div>
                        </div>
        <?php
        }
		elseif($columnCount == 2) {
        ?>
						<div class="col-sm-12 col-lg-6 d-sm-none">
                            <div class="card-body"></div>
                        </div>
    <?php
        }
		elseif($columnCount == 1) {
    ?>
						<div class="col-sm-12 col-lg-3 d-sm-none">
                            <div class="card-body"></div>
                        </div>
    <?php
        }
    ?>
                    </div>
                </div>
            </div>
        </div>
    <?php
    }

    private function getSoldierMissions($missionstmt, $covertstmt, int $missionCovertCount, array $rankInfo, array $classInfo): void {
        ?>
        <div class="row soldier-mission-list">
            <div class="card page-head bg-success text-white">
                <div class="card-header">Missions</div>
            </div>
        </div>

        <div class="row mission-row-head">
            <div class="col-12 col-md-6 col-lg-3 col-xxl-2">Mission</div>
            <div class="col-12 col-md-6 col-lg-3">Mission Details</div>
            <div class="col-2 col-sm-1 rank-head">Rank</div>
            <div class="col-3 col-md-2 col-lg-1">Shots</div>
            <div class="col-3 col-md-2 col-lg-1">OW</div>
            <div class="col-3 col-md-2 col-lg-1">Other</div>
            <div class="col-1 col-lg-1">Dmg</div>
            <div class="col-2 col-lg-1">Kills</div>
            <div class="col-4 col-md-5 col-lg-2 col-xxl-1">Status</div>
        </div>
        <?php

        $currentRank = "";
        $currentRankName = "";
        $currentRankNum = 0;

        $missionRow = $missionstmt->fetch();
        $covertRow = $covertstmt->fetch();

        for( $i = 0; $i < $missionCovertCount; $i++ ) {
            if($missionRow) {
                $mission = new Mission;
                $mission->getMission(intval($missionRow['mission']));

                $soldierMission = new MissionSoldier;
                $soldierMission->getMissionSoldier(intval($missionRow['soldier_mission']));
                $missionDate = $mission->missionDate;
            } else {
                $missionDate = 0;
            }

            if($covertRow) {
                $covert = new CovertAction;
                $covert->getCovertAction(intval($covertRow['action']));

                $operative = new CovertOperative;
                $operative->getCovertOperative(intval($covertRow['operative']));
                $covertDate = $covert->endDate;
            } else {
                $covertDate = 0;
            }

            if(($missionDate >= $covertDate)) {
                if ($soldierMission->killedAliens > 0 and $soldierMission->killedLost == 0) {
                    $killedText = '<span class="soldier-mission-aliens">' . $soldierMission->killedAliens . ' (A)</span>';
                } elseif ($soldierMission->killedAliens == 0 and $soldierMission->killedLost > 0) {
                    $killedText = '<span class="soldier-mission-lost">' . $soldierMission->killedLost . ' (L)</span>';
                } elseif ($soldierMission->killedAliens > 0 and $soldierMission->killedLost > 0) {
                    $killedText = '<span class="soldier-mission-aliens">' . $soldierMission->killedAliens . ' (A)</span><span class="soldier-mission-lost">' . $soldierMission->killedLost . ' (L)</span>';
                } else {
                    $killedText = "0";
                }

                $statusString = $soldierMission->mvp == 1 ? '<span class="badge bg-success">MVP</span>' : '';
                $statusString .= $soldierMission->promoted == 1 ? '<span class="badge bg-success">Promoted</span>' : '';

                $statusString .= $soldierMission->extra == "Hacked" ? '<span class="badge bg-info text-dark">Hacked</span>' : '';
                $statusString .= $soldierMission->extra == "Mind Controlled" ? '<span class="badge bg-info text-dark">Hacked</span>' : '';
                $statusString .= $soldierMission->extra == "Granted ADVENT" ? '<span class="badge bg-info text-dark">Granted ADVENT</span>' : '';
                $statusString .= $soldierMission->extra == "Granted Resistance" ? '<span class="badge bg-info text-dark">Granted Resistance</span>' : '';
                $statusString .= $soldierMission->extra == "Resistance Soldiers" ? '<span class="badge bg-info text-dark">Resistance Soldiers</span>' : '';
                $statusString .= $soldierMission->extra == "Spawned Item/Ability" ? '<span class="badge bg-info text-dark">Spawned Item/Ability</span>' : '';
                $statusString .= $soldierMission->extra == "Resistance Operative" ? '<span class="badge bg-info text-dark">Resistance Operative</span>' : '';
                $statusString .= $soldierMission->extra == "Ambushed" ? '<span class="badge bg-danger">Ambushed</span>' : '';
                $statusString .= $soldierMission->extra == "Skirmisher Warrior" ? '<span class="badge bg-info text-dark">Skirmisher Warrior</span>' : '';

                $statusString .= $soldierMission->status == "Lightly Wounded" ? '<span class="badge bg-warning text-dark">Lightly Wounded</span>' : '';
                $statusString .= $soldierMission->status == "Wounded" ? '<span class="badge bg-danger">Wounded</span>' : '';
                $statusString .= $soldierMission->status == "Gravely Wounded" ? '<span class="badge bg-danger">Gravely Wounded</span>' : '';
                $statusString .= $soldierMission->status == "Shaken" ? '<span class="badge bg-warning text-dark">Shaken</span>' : '';
                $statusString .= $soldierMission->status == "Captured" ? '<span class="badge bg-danger">Captured</span>' : '';
                $statusString .= $soldierMission->status == "Killed" ? '<span class="badge bg-danger">Killed</span>' : '';
                $statusString .= $soldierMission->status == "Rescued" ? '<span class="badge bg-success">Rescued</span>' : '';

                $missionInfo = $mission->missionInfo();
                $currentMissionRank = $soldierMission->smRankInfo();
                $missionStatsInfo = $soldierMission->smStatsInfo();
        ?>
            <div class="row mission-row">
                <div class="col-12 col-md-6 col-lg-3 col-xxl-2">
                    <span class="soldier-mission-name"><a href="/mission/<?php echo strtolower(str_replace(" ","~",$mission->operationName))."/"; ?>">Operation <?php echo $mission->operationName; ?></a></span>
                    <span class="soldier-mission-date"><?php echo date('F d, Y', strtotime($mission->missionDate)); ?></span>
                </div>
                <div class="col-12 col-md-6 col-lg-3">
                    <span class="soldier-mission-type"><?php echo $missionInfo['type']; ?></span>
                    <span class="soldier-mission-objective"><?php echo $missionInfo['objective']; ?></span>
                </div>
                <div class="col-2 col-sm-1 rank-icon rank-<?php echo strtolower($currentMissionRank['name']); ?>"><span class="rank-short"><?php echo $currentMissionRank['short']; ?></span></div>
                <?php
                if($missionStatsInfo['shots_taken'] > 0) { ?>
                    <div class="col-3 col-md-2 col-lg-1">
                        <span class="soldier-mission-shots"><?php echo $missionStatsInfo['shots_hit'].'/'.$missionStatsInfo['shots_taken']; ?></span>
                        <span class="soldier-mission-shots-pct"><?php echo '('.$missionStatsInfo['shots_pct'].'%)'; ?></span>
                    </div>
                <?php 			} else { ?>
                    <div class="col-3 col-md-2 col-lg-1"></div>
                <?php 			} ?>
                <?php
                if($missionStatsInfo['overwatch_taken'] > 0) { ?>
                    <div class="d-none d-xl-block col-xl-1">
                        <span class="soldier-mission-overwatch"><?php echo $missionStatsInfo['overwatch_hit'].'/'.$missionStatsInfo['overwatch_taken']; ?></span>
                        <span class="soldier-mission-overwatch-pct"><?php echo '('.$missionStatsInfo['overwatch_pct'].'%)'; ?></span>
                    </div>
                <?php 			} else { ?>
                    <div class="d-none d-xl-block col-xl-1"></div>
                <?php		 	} ?>
                <?php if($missionStatsInfo['other_taken'] > 0) { ?>
                <div class="d-none d-xl-block col-xl-1">
                    <span class="soldier-mission-other"><?php echo $missionStatsInfo['other_hit'].'/'.$missionStatsInfo['other_taken']; ?></span>
                    <span class="soldier-mission-other-pct"><?php echo '('.$missionStatsInfo['other_pct'].'%)'; ?></span>
                </div>
            <?php 			} else { ?>
                <div class="d-none d-xl-block col-xl-1"></div>
            <?php		 	} ?>
                <div class="col-1 col-sm-2 col-lg-1"><?php echo  $missionStatsInfo['damage']; ?></div>
                <div class="col-2 col-lg-1"><?php echo $killedText; ?></div>
                <div class="col-4 col-md-5 col-lg-2 col-xxl-1"><?php echo $statusString; ?></div>
            </div>
        <?php
                $currentRank = $currentMissionRank['name'];
                $currentRankName = $currentMissionRank['short'];
                $currentRankNum = $currentMissionRank['level'];

                $missionRow = $missionstmt->fetch();
            } else {
                if ($currentRank == "") {
                    $currentRankName = $rankInfo['short'];
                    $currentRank = $rankInfo['name'];
                    $currentRankNum = $rankInfo['level'];
                }
                if ($operative->promoted == 1) {
                    if ($currentRankNum == 2) {
                        $currentRank = "Rookie";
                        $currentRankName = "RK.";
                    } else {
                        $level = $currentRankNum - 1;
                        $currentRankNum = $level;

                        $query = "SELECT xrank.name, xrank.short
                                FROM xcom_rank as xrank 
                                    INNER JOIN xcom_class_rank as cr ON cr.rank_id = xrank.id
                                    INNER JOIN xcom_class as class ON cr.class_id = class.id
                                WHERE class.name = :class and xrank.level = :level";
                        $params[0] = array("param" => ":class", "var" => $classInfo['name'], "type" => PDO::PARAM_STR,);
                        $params[1] = array("param" => ":level", "var" => $level, "type" => PDO::PARAM_INT,);
                        $rankResult = Database::runQuery('select', $query, $params);
                        $row = $rankResult->fetch();
                        $currentRank = $row['name'];
                        $currentRankName = $row['short'];
                    }
                }

                $covertTypeObj = new CovertType;
                $covertTypeObj->getCovertType($covert->type);
                $covertType = $covertTypeObj->name;
    ?>
                <div class="row mission-row">
                    <div class="col-6 col-md-2">
                        <span class="soldier-covert-start-date"><?php echo date('M d, Y', strtotime($covert->startDate)); ?></span>
                        <span class="soldier-covert-end-date"><?php echo date('M d, Y', strtotime($covert->endDate)); ?></span>
                    </div>
                    <div class="col-6 col-md-4 col-lg-3">
                        <span class="soldier-covert-action">Covert Action</span>
                        <span class="soldier-covert-action-type"><?php echo $covertType; ?></span>
                    </div>
                    <div class="col-2 col-md-1 col-lg-2 col-xl-1 rank-icon rank-<?php echo strtolower($currentRank); ?>"><span class="rank-short"><?php echo $currentRankName; ?></span></div>
                    <?php
                    if($covert->faction == "Reapers") { ?>
                        <div class="d-none d-sm-block col-sm-1 class-icon"><img src="/img/class/reaper.png" alt="Reapers" /></div>
                        <div class="col-4 col-sm-3 col-md-2 col-xl-4 class-covert">Reapers</div>
                    <?php			}
                    elseif($covert->faction == "Skirmishers") { ?>
                        <div class="d-none d-sm-block col-sm-1 class-icon"><img src="/img/class/skirmishers.png" alt="Skirmishers" /></div>
                        <div class="col-4 col-sm-3 col-md-2 col-xl-4 class-covert">Skirmishers</div>
                    <?php			}
                    elseif($covert->faction == "Templars") { ?>
                        <div class="d-none d-sm-block col-sm-1 class-icon"><img src="/img/class/templars.png" alt="Templars" /></div>
                        <div class="col-4 col-sm-3 col-md-2 col-xl-4 class-covert">Templars</div>
                    <?php			} ?>
                    <div class="col-6 col-md-2 col-xl-1">
                        <?php			if($covert->status == "In Progress") { ?>
                            <span class="badge bg-warning text-dark">In Progress</span>
                        <?php			}
                        elseif($covert->status == "Ambushed") { ?>
                            <span class="badge bg-danger">Ambushed</span>
                        <?php			}
                        if($operative->promoted == 1 and $covert->status != "In Progress") { ?>
                            <span class="badge bg-success">Promoted</span>
                        <?php			}
                        if($operative->status == "Wounded") { ?>
                            <span class="badge bg-danger">Wounded</span>
                        <?php			}
                        elseif($operative->status == "Captured") { ?>
                            <span class="badge bg-danger">Captured</span>
                        <?php			} ?>
                    </div>
                </div>
    <?php
                $covertRow = $covertstmt->fetch();
            }
        }
    }

    private function getBondInfo(int $soldierID): array {
        $query = "SELECT * from xcom_bonds WHERE (soldier_id1 = :id OR soldier_id2 = :id) AND active = 1";
        $params[0] = array("param" => ":id", "var" => $soldierID, "type" => PDO::PARAM_INT,);
        $bondResult = Database::runQuery('select', $query, $params);

        $bondInfo['level'] = "";
        $bondInfo['link'] = "";
        $bondInfo['name'] = "";
        if($bondResult->rowCount() == 1) {
            $bond = $bondResult->fetch();
            $bondedSoldier = new Soldier;
            if($bond['soldier_id1'] == $soldierID){
                $bondedSoldier->getSoldier(intval($bond['soldier_id2']));
            }
            elseif($bond['soldier_id2'] == $soldierID) {
                $bondedSoldier->getSoldier(intval($bond['soldier_id1']));
            }

            if($bondedSoldier->nickname != "") {
                $bondInfo['name'] = $bondedSoldier->firstName.' "'.$bondedSoldier->nickname.'" '.$bondedSoldier->lastName;
            } else {
                $bondInfo['name'] = $bondedSoldier->firstName.' '.$bondedSoldier->lastName;
            }
            $bondInfo['link'] = "/soldier/".strtolower($bondedSoldier->firstName)."~".strtolower($bondedSoldier->lastName)."/";
            $bondInfo['level'] = $bond['bond_level'];
        } else {
            $bondInfo = [];
        }
        return $bondInfo;
    }

    private function getSoldierInfo(Soldier $soldier): array {
        $soldierInfo['class'] = $soldier->soldierClass();
        $soldierInfo['rank'] = $soldier->soldierRank();
        $soldierInfo['skills'] = $soldier->soldierSkills();
        $soldierInfo['stats'] = $soldier->soldierStats();

        if($soldierInfo['class']['name'] == "Reaper") {
            $soldierInfo['flag'] = "reapers";
            $soldierInfo['country'] = "Reapers";
        }
        elseif($soldierInfo['class']['name'] == "Skirmisher") {
            $soldierInfo['flag'] = "skirmishers";
            $soldierInfo['country'] = "Skirmishers";
        }
        elseif($soldierInfo['class']['name'] == "Templar") {
            $soldierInfo['flag'] = "templars";
            $soldierInfo['country'] = "Templars";
        }
        elseif(strstr($soldierInfo['class']['name'], "Spark")) {
            $soldierInfo['flag'] = "spark";
            $soldierInfo['country'] = $soldierInfo['class']['name'];
        }
        else {
            $soldierInfo['flag'] = str_replace(" ","-",strtolower($soldier->country));

            // Check for ADVENT
            if($soldierInfo['flag'] == "advent") {
                $soldierInfo['flag'] = "advent-dark";
            }

            $soldierInfo['country'] = $soldier->country;
        }

        if($soldier->nickname == "") {
            $soldierInfo['soldierName'] = $soldier->firstName." ".$soldier->lastName;
            $soldierInfo['soldierAltName'] = $soldier->firstName." ".$soldier->lastName;
        } else {
            $soldierInfo['soldierName'] = $soldier->firstName.' "'.$soldier->nickname.'" '.$soldier->lastName;
            $soldierInfo['soldierAltName'] = $soldier->firstName." '".$soldier->nickname."' ".$soldier->lastName;
        }

        return $soldierInfo;
    }

    private function getMissionInfo(int $soldierID): array {
        $query = "SELECT mission.id as mission, sm.id as soldier_mission
			FROM xcom_mission as mission
				INNER JOIN xcom_mission_soldier as sm on mission.id = sm.mission_id 
			WHERE sm.soldier_id = :id AND mission.status != 3
			ORDER BY mission.mission_date DESC, mission.episode DESC, mission.id DESC";
        $params = array();
        $params[0] = array("param" => ":id", "var" => $soldierID, "type" => PDO::PARAM_INT,);
        $missionResult = Database::runQuery('select', $query, $params);

        $query = "SELECT action.id as action, operative.id as operative 
			FROM xcom_covert_action as action
				INNER JOIN xcom_covert_operative as operative on action.id = operative.action_id 
			WHERE operative.soldier_id = :id
			ORDER BY action.start_date DESC";
        $params = array();
        $params[0] = array("param" => ":id", "var" => $soldierID, "type" => PDO::PARAM_INT,);
        $covertResult = Database::runQuery('select', $query, $params);

        $missionInfo['missionCount'] = $missionResult->rowCount();
        $missionInfo['covertCount'] = $covertResult->rowCount();
        $missionInfo['totalCount'] = $missionInfo['missionCount'] + $missionInfo['covertCount'];
        $missionInfo['missionResult'] = $missionResult;
        $missionInfo['covertResult'] = $covertResult;

        return $missionInfo;
    }
}