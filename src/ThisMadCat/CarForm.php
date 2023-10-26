<?php

namespace ThisMadCat;
use ThisMadCat\STMC;
use pocketmine\Player;

class CarForm{

		    private $plug;
		    function __construct(Car $plug){
		        $this->plug = $plug;
		    }
		    function open(Player $pl){
		        $f = $this->plug->form->createCustomForm(function (Player $pl, $data){
						$gm = $this->plug->eco->myMoney($pl);
					if($data[0] == NULL){
            if($gm >= 200000){
              $pl->sendMessage("Ты купил автомобиль 1-го уровня за 200000$ /car - завести/заглушить двигатель.");
              $this->plug->eco->reduceMoney($pl, 200000);
              $pdata = $this->plug->players->getAll();
              $pdata[$pl->getName()]['car'] = 1;
              $pdata[$pl->getName()]['fuel'] = 20;
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
