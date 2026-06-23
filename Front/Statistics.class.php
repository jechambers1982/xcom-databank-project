<?php
declare(strict_types = 1);

namespace XCOMDatabank\Front;

use XCOMDatabank\Utility\Database;

class Statistics
{
    /* Summary Blocks
        getSoldierSummary - Returns Number of total Soldiers and number of killed Soldiers
        getMissionSummary - Returns Number of total missions as well as number of Completed and Failed Missions
        getCovertSummary - Returns Number of covert actions and activity chains
        getAlienSummary - Returns number and percent of encountered and killed aliens and lost
    */

    public static function getSoldierSummary(): void {
        $query = "SELECT id FROM xcom_soldier";
        $params = array();
        $queryResult = Database::runQuery('select', $query, $params);

        $soldierArray['total'] = $queryResult->rowCount();

        $query = "SELECT id FROM xcom_soldier where killed = 1";
        $queryResult = Database::runQuery('select', $query, $params);

        $soldierArray['killed'] = $queryResult->rowCount();
        ?>
        <div class="card-header border-success alert-success">
            <div class="stat-summary mb-0">Soldier Statistics</div>
        </div>
        <div class="card-body">
            <div class="stat-summary-body"><strong>Total:</strong> <?php echo $soldierArray['total']; ?></div>
            <div class="stat-summary-body"><strong>Killed:</strong> <?php echo $soldierArray['killed']; ?></div>
        </div>
        <?php
    }

    public static function getMissionSummary(): void {
        $query = "SELECT id FROM xcom_mission";
        $params = array();
        $queryResult = Database::runQuery('select', $query, $params);

        $missionArray['total'] = $queryResult->rowCount();

        $query = "SELECT id FROM xcom_mission where status = 1";
        $queryResult = Database::runQuery('select', $query, $params);

        $missionArray['completed'] = $queryResult->rowCount();

        $query = "SELECT id FROM xcom_mission where status = 2 or status = 3";
        $queryResult = Database::runQuery('select', $query, $params);

        $missionArray['failed'] = $queryResult->rowCount();
        ?>
        <div class="card-header border-primary alert-primary">
            <div class="stat-summary mb-0">Mission Statistics</div>
        </div>
        <div class="card-body">
            <div class="stat-summary-body"><strong>Total:</strong> <?php echo $missionArray['total']; ?></div>
            <div class="stat-summary-body"><strong>Completed:</strong> <?php echo $missionArray['completed']; ?> | <strong>Failed:</strong> <?php echo $missionArray['failed']; ?></div>
        </div>
        <?php
    }

    public static function getCovertSummary(): void {
        $query = "SELECT id FROM xcom_covert_action";
        $params = array();
        $queryResult = Database::runQuery('select', $query, $params);

        $covertArray['covert-actions'] = $queryResult->rowCount();

        $query = "SELECT id FROM xcom_activity_chain";
        $queryResult = Database::runQuery('select', $query, $params);

        $covertArray['activity-chains'] = $queryResult->rowCount();
        ?>
        <div class="card-header border-warning alert-warning">
            <div class="stat-summary mb-0">Covert Statistics</div>
        </div>
        <div class="card-body">
            <div class="stat-summary-body"><strong>Covert Actions:</strong> <?php echo $covertArray['covert-actions']; ?></div>
            <div class="stat-summary-body"><strong>Activity Chains:</strong> <?php echo $covertArray['activity-chains']; ?></div>
        </div>
        <?php
    }

    public static function getAlienSummary(): void {
        $query = "SELECT sum(ma.encountered) as encountered, sum(ma.killed) as killed from xcom_mission_alien as ma
            INNER JOIN xcom_aliens as alien ON alien.id = ma.alien_id
            INNER JOIN xcom_alien_type as type ON type.id = alien.type_id
            WHERE type.name != 'The Lost'";
        $params = array();
        $queryResult = Database::runQuery('select', $query, $params);
        $aliens = $queryResult->fetch();
        $aliens['pct'] = round($aliens['killed'] / $aliens['encountered'], 3) * 100;

        $query = "SELECT sum(ma.encountered) as encountered, sum(ma.killed) as killed from xcom_mission_alien as ma
            INNER JOIN xcom_aliens as alien ON alien.id = ma.alien_id
            INNER JOIN xcom_alien_type as type ON type.id = alien.type_id
            WHERE type.name = 'The Lost'";
        $queryResult = Database::runQuery('select', $query, $params);
        $lost = $queryResult->fetch();
		if($lost['encountered'] > 0) {
			$lost['pct'] = round($lost['killed'] / $lost['encountered'], 3) * 100;
		} else {
			$lost['pct'] = 0;
		}

        ?>
        <div class="card-header border-danger alert-danger">
            <div class="stat-summary mb-0">Alien Statistics</div>
        </div>
        <div class="card-body">
            <div class="stat-summary-body"><strong>Aliens:</strong> <?php echo $aliens['killed'].' / '.$aliens['encountered'].' ('.$aliens['pct'].'%)'; ?></div>
            <div class="stat-summary-body"><strong>Lost:</strong> <?php echo $lost['killed'].' / '.$lost['encountered'].' ('.$lost['pct'].'%)'; ?></div>
        </div>
        <?php
    }

    /* Soldier Statistics Blocks
        getTopSoldiers - Returns the Top 10 soldiers based on available MVP parameters
        getRanksBySoldiers - Returns each rank with the number of soldiers of that rank
        getClassBySoldiers - Returns each class with the number of soldiers of that class
        getSoldierMVPs - Gets up to top 10 soldiers by Mission MVPs won
    */

    public static function getTopSoldiers(): void {
        $query = "SELECT soldier.first_name as first, soldier.nickname as nickname, soldier.last_name as last, sum(ma.shots_taken) as taken, 
                        sum(ma.shots_hit) as hit, sum(ma.damage) as damage, sum(ma.killed_aliens) as aliens, sum(ma.killed_lost) as lost,
                        sum(ma.other_taken) as otherTaken, sum(ma.other_hit) as otherHit, sum(ma.healing) as healing
                    FROM xcom_soldier as soldier
                    INNER JOIN xcom_mission_soldier as ma ON ma.soldier_id = soldier.id
                    GROUP BY soldier.id";
        $params = array();
        $queryResult = Database::runQuery('select', $query, $params);

        $soldierArray = array();
        while ($row = $queryResult->fetch()) {
            $name = $row['first'].' "'.$row['nickname'].'" '.$row['last'];
            $shotPoints = $row['hit'] - ($row['taken'] - $row['hit']);
            $otherPoints = $row['otherHit'] - ($row['otherTaken'] - $row['otherHit']);
            $damagePoints = $row['damage'];
            $killPoints = ($row['aliens'] * 10) + ($row['lost'] * 2);
            $healPoints = $row['healing'];
            $soldierArray[$name] = $shotPoints + $otherPoints + $damagePoints + $killPoints + $healPoints;
        }
        arsort($soldierArray, SORT_NUMERIC);

        ?>
        <div class="stats-header border border-secondary alert-secondary rounded-top">
            <div class="stat-summary mb-0">Season MVP Rankings</div>
        </div>
        <div class="stats-body border border-top-0 border-secondary alert-light ps-3 py-3 pe-2 rounded-bottom">
        <?php
            for ($x = 0; $x < 10; $x++) {
        ?>
                <div class="row gx-0">
                    <div class="col-1"><?php echo ($x + 1).'.'; ?></div>
                    <div class="col-9"><?php echo key($soldierArray); ?></div>
                    <div class="col-2 text-end"><?php echo '('.number_format((float)current($soldierArray),0,'',',').')'; ?></div>
                </div>
        <?php
                next($soldierArray);
            }
        ?>
        </div>
        <?php
    }

    public static function getRanksBySoldiers(): void {
        $query = "SELECT xrank.level as xrank, count(soldier.rank_id) as number 
            FROM xcom_rank as xrank
            INNER JOIN xcom_soldier as soldier ON soldier.rank_id = xrank.id
            GROUP BY xrank.level
            ORDER BY xrank.level DESC";
        $params = array();
        $queryResult = Database::runQuery('select', $query, $params);
        $rankArray = array();
        while ($row = $queryResult->fetch()) {
            switch($row['xrank']) {
                case 8:
                    $rankArray['Colonel'] = $row['number'];
                    break;
                case 7:
                    $rankArray['Major'] = $row['number'];
                    break;
                case 6:
                    $rankArray['Captain'] = $row['number'];
                    break;
                case 5:
                    $rankArray['Lieutenant'] = $row['number'];
                    break;
                case 4:
                    $rankArray['Sergeant'] = $row['number'];
                    break;
                case 3:
                    $rankArray['Corporal'] = $row['number'];
                    break;
                case 2:
                    $rankArray['Squaddie'] = $row['number'];
                    break;
                case 1:
                    $rankArray['Rookie'] = $row['number'];
                    break;
            }
        }
        ?>
        <div class="stats-header border border-secondary alert-secondary rounded-top">
            <div class="stat-summary mb-0">Soldiers by Rank</div>
        </div>
        <div class="stats-body border border-top-0 border-secondary alert-light p-3 rounded-bottom">
        <?php
        foreach($rankArray as $key => $value) {
        ?>
            <div class="row gx-0">
                <div class="col-9"><?php echo $key; ?></div>
                <div class="col-3 text-end"><?php echo $value; ?></div>
            </div>
        <?php
        }
        ?>
        </div>
        <?php
    }

    public static function getClassBySoldiers(): void {
        $query = "SELECT class.name as class, count(soldier.class_id) as number 
            FROM xcom_class as class
            INNER JOIN xcom_soldier as soldier ON soldier.class_id = class.id
            GROUP BY class
            ORDER BY number DESC, class";
        $params = array();
        $queryResult = Database::runQuery('select', $query, $params);
        ?>
        <div class="stats-header border border-secondary alert-secondary rounded-top">
            <div class="stat-summary mb-0">Soldiers by Class</div>
        </div>
        <div class="stats-body border border-top-0 border-secondary alert-light p-3 rounded-bottom">
            <div class="row gx-0">
        <?php
        $rowCount = 0;
        while($row = $queryResult->fetch()) {
            if($rowCount == 0) {
        ?>
                <div class="col-12 col-md-6">
                    <div class="row g-0">
        <?php
            }
        ?>
                <div class="col-10"><?php echo $row['class']; ?></div>
                <div class="col-2"><?php echo $row['number']; ?></div>
            <?php
            $rowCount += 1;
            if($rowCount == 10) {
                    ?>
        <?php
                $rowCount = 0;
                ?>
                </div>
                </div>
                <?php
            }
        }
        if($rowCount != 10) {
        ?>
                    </div>
                </div>
        <?php
        }
        ?>
            </div>
        </div>
        <?php
    }

    public static function getSoldierMVPs(): void {
        $query = "SELECT count(ms.mvp) as mvp, soldier.first_name, soldier.last_name, soldier.nickname
	                FROM xcom_mission_soldier as ms
		                INNER JOIN xcom_soldier as soldier ON soldier.id = ms.soldier_id
                    WHERE ms.mvp = true
                    GROUP BY soldier.last_name
                    ORDER BY count(ms.mvp) DESC, soldier.last_name";
        $params = array();
        $queryResult = Database::runQuery('select', $query, $params);

        $totalCount = 0;
        $currentTotal = 0;
        ?>
        <div class="stats-header border border-secondary alert-secondary rounded-top">
            <div class="stat-summary mb-0">Mission MVPs</div>
        </div>
        <div class="stats-body border border-top-0 border-secondary alert-light p-3 rounded-bottom">
        <?php
        $currentNum = -1;
        $numString = '';
        $totalRows = 0;
        $numCount = 0;
        $maxRows = 10;
        $firstRow = true;
        while($row = $queryResult->fetch() and $totalRows < $maxRows) {
            if(empty($row['nickname'])) {
                $opName = $row['first_name'].' '.$row['last_name'];
            } else {
                $opName = $row['first_name'].' "'.$row['nickname'].'" '.$row['last_name'];
            }
            if($currentNum == $row['mvp']) {
                $numText = '';
            } else {
                if($numCount > 0) {
                    if ((($totalRows + $numCount) <= $maxRows) or $firstRow == true) {
                        echo $numString;
                        $totalRows += $numCount;
                        $firstRow = false;
                    } else {
                        $numString = '<div class="row gx-0">';
                        $numString .= '<div class="col-2"><strong>' . $currentNum . ' - </strong></div>';
                        $numString .= '<div class="col-10">' . $numCount . ' Soldiers</div>';
                        $numString .= '</div>';
                        echo $numString;
                        $totalRows += 1;
                    }
                }

                $numText = $row['mvp'] . ' - ';
                $currentNum = $row['mvp'];
                $numString = '';
                $numCount = 0;
            }
            if($totalRows < $maxRows) {
                $numString .= '<div class="row gx-0">';
                $numString .= '<div class="col-2"><strong>' . $numText . '</strong></div>';
                $numString .= '<div class="col-10">' . $opName . '</div>';
                $numString .= '</div>';
                $numCount += 1;
            }
        }
        // If the loop ends before it can print out the final group
        if($numCount > 0) {
            if (($totalRows + $numCount) <= $maxRows) {
                echo $numString;
            } else {
                $numString = '<div class="row gx-0">';
                $numString .= '<div class="col-2"><strong>' . $currentNum . '</strong></div>';
                $numString .= '<div class="col-10">' . $numCount . ' Soldiers</div>';
                $numString .= '</div>';
                echo $numString;
            }
        }
        ?>
        </div>
        <?php
    }

    /* Mission Statistics Blocks
        getMissionTypes - Returns mission types by count
        getMissionDifficulty - Returns mission difficulty by count
        getMissionRatings - Returns mission ratings by count
        getChosenEncounters - Returns number of chosen encounters (and kills)
        getBattleStat - Get Battle Statistics
    */

    public static function getBattleStats(): void {
        $query = "SELECT sum(ms.shots_taken) as shotsTaken, sum(ms.shots_hit) as shotsHit, sum(ms.overwatch_taken) as overwatchTaken, 
                        sum(ms.overwatch_hit) as overwatchHit, sum(ms.other_taken) as otherTaken, sum(ms.other_hit) as otherHit, 
                        sum(ms.damage) as damage, sum(ms.healing) as healing, sum(ms.killed_aliens) as aliens, sum(ms.killed_lost) as lost
	                FROM xcom_mission_soldier as ms";
        $params = array();
        $queryResult = Database::runQuery('select', $query, $params);
        $getResults = $queryResult->fetch();
        $stats = array();

        foreach($getResults as $key => $value) {
            $stats[$key] = (float)$value;
        }

        $stats['shotsPct'] = (round($stats['shotsHit'] / $stats['shotsTaken'],3) * 100).'%';
        $stats['overwatchPct'] = (round($stats['overwatchHit'] / $stats['overwatchTaken'],3) * 100).'%';

        foreach($stats as $key => $value) {
            if($key != 'shotsPct' and $key != 'overwatchPct') {
                $stats[$key] = number_format((float)$value,0,'',',');
            }
        }

        ?>
        <div class="stats-header border border-secondary alert-secondary rounded-top">
            <div class="stat-summary mb-0">Battle Statistics</div>
        </div>
        <div class="stats-body border border-top-0 border-secondary alert-light p-3 rounded-bottom">
            <div class="row gx-0">
                <div class="col-6"><strong>Shots:</strong></div>
                <div class="col-6"><?php echo $stats['shotsHit'].' / '.$stats['shotsTaken'].' ('.$stats['shotsPct'].')'; ?></div>
            </div>
            <div class="row gx-0">
                <div class="col-6"><strong>Overwatch:</strong></div>
                <div class="col-6"><?php echo $stats['overwatchHit'].' / '.$stats['overwatchTaken'].' ('.$stats['overwatchPct'].')'; ?></div>
            </div>
            <div class="row gx-0">
                <div class="col-6"><strong>Other Attacks:</strong></div>
                <div class="col-6"><?php echo $stats['otherHit'].' / '.$stats['otherTaken']; ?></div>
            </div>
            <div class="row gx-0">
                <div class="col-6"><strong>Damage:</strong></div>
                <div class="col-6"><?php echo $stats['damage']; ?></div>
            </div>
            <div class="row gx-0">
                <div class="col-6"><strong>Aliens Killed:</strong></div>
                <div class="col-6"><?php echo $stats['aliens']; ?></div>
            </div>
            <div class="row gx-0">
                <div class="col-6"><strong>Lost Killed:</strong></div>
                <div class="col-6"><?php echo $stats['lost']; ?></div>
            </div>
            <div class="row gx-0">
                <div class="col-6"><strong>Healing:</strong></div>
                <div class="col-6"><?php echo $stats['healing']; ?></div>
            </div>
        </div>
        <?php
    }

    public static function getMissionTypes(): void {
        $query = "SELECT type.description, count(type.id) as count
                    FROM xcom_mission as mission
                        LEFT JOIN xcom_objective as objective on mission.objective_id = objective.id
                        LEFT JOIN xcom_mission_type as type on objective.mission_type_id = type.id
                    GROUP BY type.description
                    ORDER BY count(type.id) DESC, type.description";
        $params = array();
        $queryResult = Database::runQuery('select', $query, $params);
        ?>
        <div class="stats-header border border-secondary alert-secondary rounded-top">
            <div class="stat-summary mb-0">Total Missions by Type</div>
        </div>
        <div class="stats-body border border-top-0 border-secondary alert-light p-3 rounded-bottom">
            <div class="row gx-0">
        <?php
        $rowCount = 0;
        while($row = $queryResult->fetch()) {
            if($rowCount == 0) {
                ?>
                <div class="col-12 col-md-6">
                    <div class="row g-0">
                <?php
            }
            ?>
                        <div class="col-10"><?php echo $row['description']; ?></div>
                            <div class="col-2"><?php echo $row['count']; ?></div>
            <?php
            $rowCount += 1;
            if($rowCount == 15) {
                ?>
                    </div>
                </div>
                <?php
                $rowCount = 0;
            }
        }
        if($rowCount != 15) {
            ?>
                    </div>
                </div>
            <?php
        }
        ?>
            </div>
        </div>
        <?php
    }

    public static function getChosenEncounters(): void {
        $query = "SELECT count(mission.chosen_id) as number, chosen.type as name, SUM(if(mission.chosen_result = 'killed', 1, 0)) as killed
            FROM xcom_mission as mission
                INNER JOIN xcom_chosen as chosen ON chosen.id = mission.chosen_id
            WHERE mission.chosen_id IS NOT NULL
            GROUP BY mission.chosen_id
            ORDER BY number DESC";
        $params = array();
        $queryResult = Database::runQuery('select', $query, $params);

        ?>
        <div class="stats-header border border-secondary alert-secondary rounded-top mt-3">
            <div class="stat-summary mb-0">Chosen Encounters</div>
        </div>
        <div class="stats-body border border-top-0 border-secondary alert-light p-3 rounded-bottom">
            <?php
            while($row = $queryResult->fetch()) {
                ?>
                <div class="row gx-0">
                    <div class="col-6"><strong><?php echo $row['name']; ?></strong></div>
                    <div class="col-6"><?php echo $row['killed'].' / '.$row['number']; ?></div>
                </div>
                <?php
            }
            ?>
        </div>
        <?php
    }

    public static function getMissionRatings(): void {
        $query = "SELECT rating, count(rating) as number FROM xcom_mission WHERE rating != 'Infiltrating' GROUP BY rating 
                    ORDER BY FIELD(rating, 'Flawless', 'Excellent', 'Good', 'Fair', 'Poor')";
        $params = array();
        $queryResult = Database::runQuery('select', $query, $params);

        ?>
        <div class="stats-header border border-secondary alert-secondary rounded-top">
            <div class="stat-summary mb-0">Missions By Rating</div>
        </div>
        <div class="stats-body border border-top-0 border-secondary alert-light p-3 rounded-bottom">
            <?php
            while($row = $queryResult->fetch()) {
                ?>
                <div class="row gx-0">
                    <div class="col-6"><strong><?php echo $row['rating']; ?></strong></div>
                    <div class="col-6"><?php echo $row['number']; ?></div>
                </div>
                <?php
            }
            ?>
        </div>
        <?php
    }

    public static function getMissionDifficulty(): void {
        $query = "SELECT difficulty, count(difficulty) as number FROM xcom_mission WHERE rating != 'Infiltrating' GROUP BY difficulty 
                    ORDER BY FIELD(difficulty, 'Easy', 'Moderate', 'Difficult', 'Very Difficult')";
        $params = array();
        $queryResult = Database::runQuery('select', $query, $params);

        ?>
        <div class="stats-header border border-secondary alert-secondary rounded-top mt-3">
            <div class="stat-summary mb-0">Missions by Difficulty</div>
        </div>
        <div class="stats-body border border-top-0 border-secondary alert-light p-3 rounded-bottom">
            <?php
            while($row = $queryResult->fetch()) {
                ?>
                <div class="row gx-0">
                    <div class="col-6"><strong><?php echo $row['difficulty']; ?></strong></div>
                    <div class="col-6"><?php echo $row['number']; ?></div>
                </div>
                <?php
            }
            ?>
        </div>
        <?php
    }

    /* Covert Statistics Blocks
        getActionsBySoldier - get Number of covert Actions by soldier (list how many, though?)
        getActionsByType - get Number of covert Actions by Type
        getActionsByFaction - get Number of covert Actions by Faction
        getChainsByType - get Number of Activity Chains by Type
        getChainsByStatus - List how many chains are Successful/Failed/Abandoned/Ongoing
    */

    public static function getActionsBySoldier(): void {
        $query = "SELECT soldier.first_name as firstName, soldier.last_name as lastName, soldier.nickname as nickname, count(soldier_id) as number
            FROM xcom_soldier as soldier
            INNER JOIN xcom_covert_operative as operative ON operative.soldier_id = soldier.id
            GROUP BY lastName
            ORDER BY number DESC, lastName";
        $params = array();
        $queryResult = Database::runQuery('select', $query, $params);

        ?>
        <div class="stats-header border border-secondary alert-secondary rounded-top">
            <div class="stat-summary mb-0">Covert Actions by Soldier</div>
        </div>
        <div class="stats-body border border-top-0 border-secondary alert-light p-3 rounded-bottom">
            <?php
            $currentNum = -1;
            $numString = '';
            $totalRows = 0;
            $numCount = 0;
            $maxRows = 12;
            $firstRow = true;
            while($row = $queryResult->fetch() and $totalRows < $maxRows) {
                if(empty($row['nickname'])) {
                    $opName = $row['firstName'].' '.$row['lastName'];
                } else {
                    $opName = $row['firstName'].' "'.$row['nickname'].'" '.$row['lastName'];
                }
                if($currentNum == $row['number']) {
                    $numText = '';
                } else {
                    if($numCount > 0) {
                        if ((($totalRows + $numCount) <= $maxRows) or $firstRow == true) {
                            echo $numString;
                            $totalRows += $numCount;
                            $firstRow = false;
                        } else {
                            $numString = '<div class="row gx-0">';
                            $numString .= '<div class="col-2"><strong>' . $currentNum . ' - </strong></div>';
                            $numString .= '<div class="col-10">' . $numCount . ' Operatives</div>';
                            $numString .= '</div>';
                            echo $numString;
                            $totalRows += 1;
                        }
                    }

                    $numText = $row['number'] . ' - ';
                    $currentNum = $row['number'];
                    $numString = '';
                    $numCount = 0;
                }
                if($totalRows < $maxRows) {
                    $numString .= '<div class="row gx-0">';
                    $numString .= '<div class="col-2"><strong>' . $numText . '</strong></div>';
                    $numString .= '<div class="col-10">' . $opName . '</div>';
                    $numString .= '</div>';
                    $numCount += 1;
                }
            }
            // If the loop ends before it can print out the final group
            if($numCount > 0) {
                if (($totalRows + $numCount) <= $maxRows) {
                    echo $numString;
                } else {
                    $numString = '<div class="row gx-0">';
                    $numString .= '<div class="col-2"><strong>' . $currentNum . '</strong></div>';
                    $numString .= '<div class="col-10">' . $numCount . ' Operatives</div>';
                    $numString .= '</div>';
                    echo $numString;
                }
            }
            ?>
        </div>
        <?php
    }

    public static function getActionsByType(): void {
        $query = "SELECT type.name as name, count(action.type_id) as number
                    FROM xcom_covert_type as type
                    INNER JOIN xcom_covert_action as action ON action.type_id = type.id
                    GROUP BY name
                    ORDER BY number DESC, name";
        $params = array();
        $queryResult = Database::runQuery('select', $query, $params);

        ?>
        <div class="stats-header border border-secondary alert-secondary rounded-top">
            <div class="stat-summary mb-0">Covert Actions by Type</div>
        </div>
        <div class="stats-body border border-top-0 border-secondary alert-light p-3 rounded-bottom">
            <?php
            $currentNum = -1;
            $numString = '';
            $totalRows = 0;
            $numCount = 0;
            $maxRows = 12;
            $firstRow = true;
            while($row = $queryResult->fetch() and $totalRows < $maxRows) {
                if($currentNum == $row['number']) {
                    $numText = '';
                } else {
                    if($numCount > 0) {
                        if ((($totalRows + $numCount) <= $maxRows) or $firstRow == true) {
                            echo $numString;
                            $totalRows += $numCount;
                            $firstRow = false;
                        } else {
                            $numString = '<div class="row gx-0">';
                            $numString .= '<div class="col-2"><strong>' . $currentNum . ' - </strong></div>';
                            $numString .= '<div class="col-10">' . $numCount . ' Covert Action Types</div>';
                            $numString .= '</div>';
                            echo $numString;
                            $totalRows += 1;
                        }
                    }

                    $numText = $row['number'] . ' - ';
                    $currentNum = $row['number'];
                    $numString = '';
                    $numCount = 0;
                }
                if($totalRows < $maxRows) {
                    $numString .= '<div class="row gx-0">';
                    $numString .= '<div class="col-2"><strong>' . $numText . '</strong></div>';
                    $numString .= '<div class="col-10">' . $row['name'] . '</div>';
                    $numString .= '</div>';
                    $numCount += 1;
                }
            }
            // If the loop ends before it can print out the final group
            if($numCount > 0) {
                if (($totalRows + $numCount) <= $maxRows) {
                    echo $numString;
                } else {
                    $numString = '<div class="row gx-0">';
                    $numString .= '<div class="col-2"><strong>' . $currentNum . '</strong></div>';
                    $numString .= '<div class="col-10">' . $numCount . ' Covert Action Types</div>';
                    $numString .= '</div>';
                    echo $numString;
                }
            }
            ?>
        </div>
        <?php
    }

    public static function getActionsByFaction(): void {
        $query = "SELECT faction, count(id) as number FROM xcom_covert_action
            GROUP BY faction
            ORDER BY number DESC, faction";
        $params = array();
        $queryResult = Database::runQuery('select', $query, $params);

        ?>
        <div class="stats-header border border-secondary alert-secondary rounded-top">
            <div class="stat-summary mb-0">CA by Faction</div>
        </div>
        <div class="stats-body border border-top-0 border-secondary alert-light p-3 rounded-bottom">
            <?php
            while($row = $queryResult->fetch()) {
                ?>
                <div class="row gx-0">
                    <div class="col-9"><strong><?php echo $row['faction']; ?></strong></div>
                    <div class="col-3"><?php echo $row['number']; ?></div>
                </div>
                <?php
            }
            ?>
        </div>
        <?php
    }

    public static function getChainsByType(): void {
        $query = "SELECT type.name as name, count(chain.chain_type) as number
            FROM xcom_activity_chain_type as type
            INNER JOIN xcom_activity_chain as chain ON chain.chain_type = type.id
            GROUP BY name
            ORDER BY number DESC, name";
        $params = array();
        $queryResult = Database::runQuery('select', $query, $params);

        ?>
        <div class="stats-header border border-secondary alert-secondary rounded-top">
            <div class="stat-summary mb-0">Activity Chains by Type</div>
        </div>
        <div class="stats-body border border-top-0 border-secondary alert-light p-3 rounded-bottom">
            <?php
            $currentNum = -1;
            $numString = '';
            $totalRows = 0;
            $numCount = 0;
            $maxRows = 12;
            $firstRow = true;
            while($row = $queryResult->fetch() and $totalRows < $maxRows) {
                if($currentNum == $row['number']) {
                    $numText = '';
                } else {
                    if($numCount > 0) {
                        if ((($totalRows + $numCount) <= $maxRows) or $firstRow == true) {
                            echo $numString;
                            $totalRows += $numCount;
                            $firstRow = false;
                        } else {
                            $numString = '<div class="row gx-0">';
                            $numString .= '<div class="col-2"><strong>' . $currentNum . ' - </strong></div>';
                            $numString .= '<div class="col-10">' . $numCount . ' Activity Chain Types</div>';
                            $numString .= '</div>';
                            echo $numString;
                            $totalRows += 1;
                        }
                    }

                    $numText = $row['number'] . ' - ';
                    $currentNum = $row['number'];
                    $numString = '';
                    $numCount = 0;
                }
                if($totalRows < $maxRows) {
                    $numString .= '<div class="row gx-0">';
                    $numString .= '<div class="col-2"><strong>' . $numText . '</strong></div>';
                    $numString .= '<div class="col-10">' . $row['name'] . '</div>';
                    $numString .= '</div>';
                    $numCount += 1;
                }
            }
            // If the loop ends before it can print out the final group
            if($numCount > 0) {
                if (($totalRows + $numCount) <= $maxRows) {
                    echo $numString;
                } else {
                    $numString = '<div class="row gx-0">';
                    $numString .= '<div class="col-2"><strong>' . $currentNum . '</strong></div>';
                    $numString .= '<div class="col-10">' . $numCount . ' Activity Chain Types</div>';
                    $numString .= '</div>';
                    echo $numString;
                }
            }
            ?>
        </div>
        <?php
    }

    public static function getChainsByStatus(): void {
        $query = "SELECT status, count(id) as number FROM xcom_activity_chain
                    GROUP BY status
                    ORDER BY number DESC, status";
        $params = array();
        $queryResult = Database::runQuery('select', $query, $params);

        ?>
        <div class="stats-header border border-secondary alert-secondary rounded-top mt-3">
            <div class="stat-summary mb-0">Chain by Status</div>
        </div>
        <div class="stats-body border border-top-0 border-secondary alert-light p-3 rounded-bottom">
            <?php
            while($row = $queryResult->fetch()) {
                ?>
                <div class="row gx-0">
                    <div class="col-9"><strong><?php echo $row['status']; ?></strong></div>
                    <div class="col-3"><?php echo $row['number']; ?></div>
                </div>
                <?php
            }
            ?>
        </div>
        <?php
    }

    public static function getAliensByType(): void {
        $query = "SELECT sum(ma.encountered) as encountered, sum(ma.killed) as killed, type.name as name from xcom_mission_alien as ma
						LEFT JOIN xcom_aliens as aliens on ma.alien_id = aliens.id
						LEFT JOIN xcom_alien_type as type on aliens.type_id = type.id
						GROUP BY type.name
                        ORDER BY encountered DESC, killed DESC, type.name";
        $params = array();
        $queryResult = Database::runQuery('select', $query, $params);

        $columnSize = $queryResult->rowCount() / 3;
        ?>
        <div class="stats-header border border-secondary alert-secondary rounded-top">
            <div class="stat-summary mb-0">Total Aliens by Type</div>
        </div>
        <div class="stats-body border border-top-0 border-secondary alert-light p-3 rounded-bottom"> <!-- Open One -->
        <div class="row gx-0"> <!-- Open Two -->
        <?php
        $rowCount = 0;
        while($row = $queryResult->fetch()) {
            if($rowCount == 0) {
                ?>
                <div class="col-12 col-md-4"> <!-- Open Three -->
                <div class="row g-0"> <!-- Open Four -->
                <?php
            }
            ?>
            <div class="col-6"><strong><?php echo $row['name']; ?></strong></div>
            <div class="col-6"><?php echo $row['killed'].' / '.$row['encountered']; ?></div>
            <?php
            $rowCount += 1;
            if($rowCount >= $columnSize) {
                ?>
                </div> <!-- Close Four -->
                </div> <!-- Close Three -->
                <?php
                $rowCount = 0;
            }
        }
		if($rowCount < $columnSize) {
            ?>
            </div> <!-- Close Four -->
            </div> <!-- Close Three -->
            <?php
        }
          ?>
          </div> <!-- Close Two -->
          </div> <!-- Close One -->
          <?php
        ?>
        <?php
    }

    public static function getAliensByUnit(): void {
        $query = "SELECT sum(ma.encountered) as encountered, sum(ma.killed) as killed, type.name as name, aliens.name as unit from xcom_mission_alien as ma
						LEFT JOIN xcom_aliens as aliens on ma.alien_id = aliens.id
						LEFT JOIN xcom_alien_type as type on aliens.type_id = type.id
						GROUP BY unit
                        ORDER BY name, encountered DESC, killed DESC, unit";
        $params = array();
        $queryResult = Database::runQuery('select', $query, $params);

        $columnSize = $queryResult->rowCount() / 3;
        ?>
        <div class="stats-header border border-secondary alert-secondary rounded-top">
            <div class="stat-summary mb-0">Total Aliens by Unit</div>
        </div>
        <div class="stats-body border border-top-0 border-secondary alert-light p-3 rounded-bottom"> <!-- Open one -->
        <div class="row gx-0"> <!-- Open Two -->
        <?php
        $rowCount = 0;
        $currentType = "";
        while($row = $queryResult->fetch()) {
            if($currentType == $row['name']) {
                ?>
                <div class="col-8"><?php echo $row['unit']; ?></div>
                <div class="col-4"><?php echo $row['killed'].' / '.$row['encountered']; ?></div>
                <?php
                $rowCount += 1;
            } else {
                if($rowCount >= $columnSize) {
                    ?>
                    </div> <!-- Close Four A -->
                    </div> <!-- Close Three A -->
                    <?php
                    $rowCount = 0;
                }
                if($rowCount == 0) {
                    ?>
                    <div class="col-12 col-md-4"> <!-- Open Three -->
                    <div class="row g-0"> <!-- Open Four -->
                    <?php
                }
                ?>
                <div class="col-12 mt-3"><strong><?php echo $row['name']; ?></strong></div>
                <div class="col-8"><?php echo $row['unit']; ?></div>
                <div class="col-4"><?php echo $row['killed'].' / '.$row['encountered']; ?></div>
                <?php
                $currentType = $row['name'];
                $columnSize += 0.5;
                $rowCount += 2;
            }
        }
        if($rowCount != $columnSize) {
            ?>
            </div> <!-- Close Four B -->
            </div> <!-- Close Three B -->
            <?php
        }
        ?>
        </div> <!-- Close Two -->
        </div> <!-- Close One -->
        <?php
    }
}