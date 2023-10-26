<?php

namespace ThisMadCat;
use ThisMadCat\STMC;
use pocketmine\Player;

class CarForm4{

		    private $plug;
		    function __construct(Car $plug){
		        $this->plug = $plug;
		    }
		    function open(Player $pl){
		        $f = $this->plug->form->createCustomForm(function (Player $pl, $data){
						$gm = $this->plug->eco->myMoney($pl);
					if($data[0] == NULL){
            if($gm >= 400000){
              $pl->sendMessage("Ты купил автомобиль 4-го уровня за 400000$ /car - завести/заглушить двигатель.");
              $this->plug->eco->reduceMoney($pl, 400000);
              $pdata = $this->plug->players->getAll();
              $pdata[$pl->getName()]['car'] = 4;
              $pdata[$pl->getName()]['fuel'] = 50;
              $this->plug->players->setAll($pdata);
              $this->plug->players->save();
						}else $pl->sendMessage("§7У вас нет такой суммы");
					}
		        });
		        $f->setTitle("§cВы уверены в покупке?");
		        $f->sendToPlayer($pl);
		        return $f;
		    }
		}
