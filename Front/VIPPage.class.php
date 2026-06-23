<?php
declare(strict_types = 1);

namespace XCOMDatabank\Front;

use PDO;
use XCOMDatabank\Utility\Database;

class VIPPage
{
    private string $position;

    public function __construct($position)
    {
        $this->position = $position;
    }

    public function getVIPS(): void {
        $query = "SELECT * FROM xcom_vip WHERE position = :position ORDER BY joined ASC, last_name, first_name";
        $params[0] = array("param" => ":position", "var" => $this->position, "type" => PDO::PARAM_STR,);
        $queryResult = Database::runQuery('select', $query, $params);

        $vipClass = str_replace(" ", "-", strtolower($this->position));
        $vipCount = $queryResult->rowCount();
        ?>

    <div class="vip-box col-lg-4 col-md-6 col-sm-12">
        <div class="vip-group">
            <div class="vip-title <?php echo $vipClass;?>"><span><?php echo "{$this->position}s ($vipCount)"; ?></span></div>
            <div class="vip-row">
                <span class="vip-name-header">Name</span>
                <span class="vip-date-header">
        <?php
        if($this->position == "Dark VIP") {
            echo "Status";
        } else {
            echo "Date Joined";
        }
        ?>
				</span>
            </div>

        <?php
        while ($row = $queryResult->fetch()) { ?>
            <div class="vip-row">
                <span class="vip-name"><?php echo $row['first_name']." ".$row['last_name']; ?></span>
                <span class="vip-date">
		<?php
            if($this->position == "Dark VIP") {
                echo $row['recruited'];
            }
            else {
                echo date('F d, Y', strtotime($row['joined']));
            }
        ?>
				</span>
            </div>
        <?php
        }
        ?>
        </div>
    </div>

        <?php
    }
}