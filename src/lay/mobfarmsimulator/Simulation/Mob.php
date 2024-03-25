<?php

namespace Lay\MobFarmSimulator\Simulation;
use pocketmine\item\Item;
use pocketmine\item\SpawnEgg;

final class Mob {

    /** @var self[] */
    private static array $mobs = []; 
    private string $name;
    private array $drops;
    private array|int $xp = 0;
    private SpawnEgg $egg;

    public static function addMob(SpawnEgg $egg, string $name, array|int $xp = 0, array $drops = []){
        $mob = new self;
        $mob->egg = $egg;
        $mob->name = $name;
        if(is_array($xp)){
            if(!($xp[0] >= 0 && $xp[1] >= 0 && $xp[1] > $xp[0])) return;
        }elseif($xp < 0){
            return;
        }
        $mob->xp = $xp;
        foreach ($drops as $drop) {
            if(!($drop[0] instanceof Item)) return;
            if(!(is_int($drop[1]) && $drop[1] >= 0)) return;
            if(array_key_exists(2, $drop)) {
                if(!(is_int($drop[2]) && $drop[2] >= 0 && $drop[2] > $drop[1])) return;
            }
        }
        $mob->drops = $drops;
        self::$mobs[$egg->getTypeId()] = $mob;
    }

    public static function getMob(string $id): ?self{
        return array_key_exists($id, self::$mobs) ? self::$mobs[$id] : null;
    }

    public function createDrops(int $mulitiplier = 1){
        $drops = [];
        $drops['xp'] = (is_int($this->xp) ? $this->xp : mt_rand($this->xp[0], $this->xp[1])) * $mulitiplier;
        foreach ($this->drops as $drop) {
            $drops[] = $drop[0]->setCount((array_key_exists(2, $drop) ?  mt_rand($drop[1], $drop[2]) : $drop[1]) * $mulitiplier);
        }
        return $drops;
    }

    public function getName(){
        return $this->name;
    }

    public function getEgg(){
        return clone $this->egg;
    }

    /**
     * @return Item[]
     */
    public function getMobDrops(){
        $drop = [];
        foreach ($this->drops as $value) { $drop[] = (clone $value[0])->setCount(1); }
        return $drop;
    }
}
