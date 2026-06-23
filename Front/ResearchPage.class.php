<?php
declare(strict_types = 1);

namespace XCOMDatabank\Front;

use XCOMDatabank\Utility\Database;

class ResearchPage
{
    private bool $isPausedFirst;
    private bool $isCompletedFirst;
    private bool $isUnlockedFirst;
    private bool $isLockedFirst;
    private string $statusClass;
    private string $dateText;
    private bool $newRow;
    private bool $openRow;

    public function __construct()
    {
        $this->isPausedFirst = true;
        $this->isCompletedFirst = true;
        $this->isUnlockedFirst = true;
        $this->isLockedFirst = true;
        $this->statusClass = "";
        $this->dateText = "";
        $this->newRow = false;
        $this->openRow = false;
    }

    public function getResearch(): void {
        $query = "SELECT * FROM xcom_research WHERE enabled = true ORDER BY
		(CASE WHEN status = 'In Progress' THEN id END) DESC,
        (CASE WHEN status = 'Paused' THEN name END) DESC,
		(CASE WHEN status = 'Complete' THEN end_date END) DESC,
		status DESC, name";
        $params = array();
        $queryResult = Database::runQuery('select', $query, $params);

        while ($row = $queryResult->fetch()) {

            if ($row['status'] == "In Progress") {
                $this->statusClass = "alert-warning border-warning";
                $this->dateText = date('M d, Y', strtotime($row['start_date'])) . " - TBD";
            } elseif ($row['status'] == "Complete") {
                $this->statusClass = "alert-success border-success";
                $this->dateText = date('M d, Y', strtotime($row['end_date']));
            } elseif ($row['status'] == "Paused") {
                $this->statusClass = "alert-secondary border-secondary";
                $this->dateText = date('M d, Y', strtotime($row['start_date'])) . " - " . date('M d, Y', strtotime($row['end_date'])) . " (Paused)";
            } elseif ($row['status'] == "Unlocked") {
                $this->statusClass = "alert-primary border-primary";
            } elseif ($row['status'] == "Locked") {
                $this->dateText = "alert-danger border-danger";
            }

            // if special = null, col should be 6 (for complete) or 12 (for not complete)
            // if special != null, col should be 4 (for complete) or 8 (for not complete)

            // if null, so colWidth should be 6 or 12
            if ($row['special'] === null) {
                //if complete, so width should be 6 (2 cols of 6 for codename and date)
                if ($row['status'] == "Complete") {
                    $codeWidth = 6;
                    $dateWidth = 6;
                } //if not complete, width should be 12 (1 column, for date)
                else {
                    $dateWidth = 12;
                    $codeWidth = 0;
                }
            } // if not null, width should be 5/3 or 8
            else {
                //if complete, and not a breakthrough, width should be 5 for codename, 3 for date
                if ($row['status'] == "Complete") {
                    // If breakthrough is complete, no code column, no width of date should be 8
                    if ($row['special'] == "Breakthrough") {
                        $dateWidth = 8;
                        $codeWidth = 0;
                    } // Otherwise, codename column is 5, date column is 3
                    else {
                        $codeWidth = 5;
                        $dateWidth = 3;
                    }
                } //if not complete, or width should be 8 (date column of 8, special column of 4, which is set)
                else {
                    $dateWidth = 8;
                    $codeWidth = 0;
                }
            }

            if ($this->isLockedFirst and $row['status'] == "Locked") {
                if($this->openRow) {
                    echo '</div>';
                    $this->openRow = false;
                }
                $this->getHeader("Locked Research Projects");
                $this->isLockedFirst = false;
                $this->newRow = true;
            }
            elseif($this->isUnlockedFirst and $row['status'] == "Unlocked") {
                if($this->openRow) {
                    echo '</div>';
                    $this->openRow = false;
                }
                $this->getHeader("Unlocked Research Projects");
                $this->isUnlockedFirst = false;
                $this->newRow = true;
            }
            elseif($this->isCompletedFirst and $row['status'] == "Complete") {
                if($this->openRow) {
                    echo '</div>';
                    $this->openRow = false;
                }
                $this->getHeader("Completed Research Projects");
                $this->isCompletedFirst = false;
                $this->newRow = true;
            }
            elseif($row['status'] == "Paused" and $this->isPausedFirst) {
                if($this->openRow) {
                    echo '</div>';
                    $this->openRow = false;
                }
                $this->getHeader("Paused Research Projects");
                $this->isPausedFirst = false;
                $this->newRow = true;
            }
            elseif($row['status'] == "In Progress") {
                if($this->openRow) {
                    echo '</div>';
                    $this->openRow = false;
                }
                $this->getHeader("Current Research Project");
                $this->newRow = true;
            }

            $this->getList($row, $codeWidth, $dateWidth);
        }
        if($this->openRow) {
            echo '</div>';
            $this->openRow = false;
        }
    }

    private function getHeader($title): void {
        ?>
        <div class="card page-head research-list-head">
            <div class="card-header bg-success text-white"><?php echo $title; ?></div>
        </div>
        <?php
    }

    private function getList($row, $codeWidth, $dateWidth): void {
        if($this->newRow) {
            echo '<div class="row">';
            $this->newRow = false;
            $this->openRow = true;
        }
        if($row['status'] == "In Progress") { ?>
                    <div class="col-12 research-box-container">
<?php   } else { ?>
                    <div class="col-12 col-md-6 research-box-container">
<?php   } ?>
                        <div class="card research-box">
                            <div class="row gx-0">
<?php   if($row['facility'] == "Research") { ?>
                                <div class="col-2 col-md-1 research-lab text-primary border-primary alert-primary"><i class="fa-solid fa-flask fa-xl"></i></div>
<?php   } elseif($row['facility'] == "Shadow Chamber") { ?>
                                <div class="col-2 col-md-1 ?> shadow-chamber alert-purple"><i class="fa-solid fa-dna fa-xl"></i></div>
<?php   } ?>
                                <div class="col-10 col-md-11 research-name <?php echo $this->statusClass; ?>"><?php echo $row['name']; ?></div>
                            </div>
<?php   if($row['status'] != "Locked" and $row['status'] != "Unlocked") { ?>
                            <div class="row gx-0">
<?php       if($row['status'] == "Complete" and $row['special'] != "Breakthrough") { ?>
                                <div class="card-body col-12 col-md-<?php echo $codeWidth; ?> research-detail"><i class="fa-regular fa-folder fa-xl"></i><strong>Codename:</strong> <?php echo $row['codename']; ?></div>
<?php       } ?>
                                <div class="card-body col-12 col-md-<?php echo $dateWidth; ?> research-detail"><i class="fa-solid fa-calendar-days fa-xl"></i><?php echo $this->dateText; ?></div>
<?php       if($row['special'] != null) { ?>
                                <div class="card-body col-12 col-md-4 research-detail text-success"><i class="fa-solid fa-star fa-xl"></i><?php echo $row['special']; ?></div>
<?php       } ?>
                            </div>
<?php   } ?>
                        </div>
                    </div>
<?php
    }
}