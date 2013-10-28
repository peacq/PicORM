<?php
namespace PicORM\tests\units;
use \atoum;

class Model extends atoum {

    public static function cleanTables() {
        \PicORM\Model::getDataSource()->query('TRUNCATE brands');
        \PicORM\Model::getDataSource()->query('TRUNCATE cars');
        \PicORM\Model::getDataSource()->query('TRUNCATE car_have_tag');
        \PicORM\Model::getDataSource()->query('TRUNCATE tags');
    }

    //// DATA PROVIDERS /////
    public static function createAndSaveRawModel() {
        self::cleanTables();
        include_once __DIR__ . '/../scripts/raw_models.php';
        $testBrand = new \Brand();
        $testBrand -> nameBrand = 'Acme';
        $testBrand -> noteBrand = 10;
        $testBrand -> save();
        return array($testBrand);
    }

    public static function createAndSaveRawModelWithOneToOneRelation() {
        self::cleanTables();
        include_once __DIR__ . '/../scripts/raw_models.php';

        $testBrand = new \Brand();
        $testBrand -> nameBrand = 'Acme';
        $testBrand -> noteBrand = 10;
        $testBrand -> save();

        $car = new \Car();
        $car -> nameCar = 'AcmeCarcreateAndSaveRawModelWithOneToOneRelation';
        $car -> noteCar = '10';
        $car -> setBrand($testBrand);
        $car -> save();

        return array(
            array($testBrand,$car)
        );
    }
    public static function createAndSaveRawModelWithManyToManyRelation() {
        self::cleanTables();
        include_once __DIR__ . '/../scripts/raw_models.php';

        $car = new \Car();
        $car -> nameCar = 'AcmeCar';
        $car -> noteCar = '10';
        $car -> idBrand = 1;
        $car -> save();

        $tags = array();

        $tag1 = new \Tag();
        $tag1 -> libTag = 'Sport';
        $tag1 -> save();
        $tag2 = new \Tag();
        $tag2 -> libTag = 'Family';
        $tag2 -> save();
        $tag3 = new \Tag();
        $tag3 -> libTag = 'Crossover';
        $tag3 -> save();
        $tags[] = $tag1;
        $tags[] = $tag2;
        $tags[] = $tag3;

        $car -> setTag($tags);
        $car -> save();
        return array(
            array($car,$tags)
        );
    }

    public static function createAndSaveRawModelWithOneToManyRelation() {
        self::cleanTables();
        include_once __DIR__ . '/../scripts/raw_models.php';

        $testBrand = new \Brand();
        $testBrand -> nameBrand = 'AcmeMult';
        $testBrand -> noteBrand = 10;
        $testBrand -> save();

        $car = new \Car();
        $car -> nameCar = 'AcmeCar1';
        $car -> noteCar = '10';

        $car2 = new \Car();
        $car2 -> nameCar = 'AcmeCar2';
        $car2 -> noteCar = '12';

        $car3 = new \Car();
        $car3 -> nameCar = 'AcmeCar3';
        $car3 -> noteCar = '15';

        $cars = array($car,$car2,$car3);

        $testBrand -> setCar($cars);

        return array(
            array($testBrand,$cars)
        );
    }

    //// END DATA PROVIDERS /////

    /**
     * @dataProvider createAndSaveRawModelWithManyToManyRelation
     */
    public function testManyToManyRelationCreation($car,$tags) {
        // create test
        $req = \PicORM\Model::getDataSource()->prepare('SELECT count(*) as nb FROM car_have_tag WHERE idCar = ?');
        $req -> execute(array($car -> idCar));
        $res = $req->fetch(\PDO::FETCH_ASSOC);
        $this -> variable($res['nb'])->isEqualTo("3");
    }

    /**
     * @dataProvider createAndSaveRawModelWithManyToManyRelation
     */
    public function testManyToManyRelation($car,$tags) {

        // get test
        $tagsGet = $car -> getTag();
        $this -> variable(count($tagsGet))->isEqualTo("3");

        $this -> variable($tagsGet[0]->libTag)->isEqualTo($tags[0]->libTag);
        $this -> variable($tagsGet[1]->libTag)->isEqualTo($tags[1]->libTag);
        $this -> variable($tagsGet[2]->libTag)->isEqualTo($tags[2]->libTag);
    }

    /**
     * @dataProvider createAndSaveRawModelWithOneToManyRelation
     */
    public function testOneToManyRelationCreation($testBrand,$testCars) {

        $req = \PicORM\Model::getDataSource()->prepare('SELECT count(*) as nb FROM cars WHERE idBrand = ?');
        $req -> execute(array($testBrand -> idBrand));
        $res = $req->fetch(\PDO::FETCH_ASSOC);
        $this -> variable($res['nb'])->isEqualTo('3');
    }

    /**
     * @dataProvider createAndSaveRawModelWithOneToManyRelation
     */
    public function testOneToManyRelation($testBrand,$testCars) {

        $cars = $testBrand -> getCar();
        $this -> variable($cars[0]->nameCar)->isEqualTo($testCars[0]->nameCar);
        $this -> variable($cars[1]->nameCar)->isEqualTo($testCars[1]->nameCar);
        $this -> variable($cars[2]->nameCar)->isEqualTo($testCars[2]->nameCar);
    }

    /**
     * @dataProvider createAndSaveRawModelWithOneToOneRelation
     */
    public function testOneToOneRelationCreation($testBrand,$car) {
        // test insert
        $req = \PicORM\Model::getDataSource()->prepare('SELECT count(*) as nb FROM cars WHERE idBrand = ? AND idCar = ?');
        $req -> execute(array($testBrand -> idBrand, $car -> idCar));
        $res = $req->fetch(\PDO::FETCH_ASSOC);
        $this -> variable($res['nb'])->isEqualTo('1');
    }

    /**
     * @dataProvider createAndSaveRawModelWithOneToOneRelation
     */
    public function testOneToOneRelation($testBrand,$car) {
        // test get relation
        $brand = $car -> getBrand();
        $this -> variable($brand->nameBrand)->isEqualTo($testBrand->nameBrand);

        // test autoget field
        $car = \Car::findOne(array('idCar'=> $car->idCar));
        $this -> variable($car->nameBrand)->isEqualTo($testBrand->nameBrand);
    }

    /**
     * @dataProvider createAndSaveRawModel
     */
    public function testDeleteModel($testBrand) {
        $idBrand = $testBrand -> idBrand;
        $testBrand -> delete();

        $req = \PicORM\Model::getDataSource()->prepare('SELECT count(*) as nb FROM brands WHERE idBrand = ?');
        $req -> execute(array($idBrand));
        $res = $req->fetch(\PDO::FETCH_ASSOC);

        $this -> variable($res['nb'])->isEqualTo("0");
    }

    /**
     * @dataProvider createAndSaveRawModel
     */
    public function testUpdateModel($testBrand) {

        $idBrand = $testBrand -> idBrand;
        $testBrand -> nameBrand = 'NEWNAME!';
        $testBrand -> noteBrand = '5';
        $testBrand -> save();

        $req = \PicORM\Model::getDataSource()->prepare('SELECT * FROM brands WHERE idBrand = ?');
        $req -> execute(array($idBrand));
        $res = $req->fetch(\PDO::FETCH_ASSOC);

        $this -> variable($res['nameBrand'])->isEqualTo('NEWNAME!');
        $this -> variable($res['noteBrand'])->isEqualTo('5');
    }

    /**
     * @dataProvider createAndSaveRawModel
     */
    public function testCreateModel($testBrand) {

        $req = \PicORM\Model::getDataSource()->prepare('SELECT * FROM brands WHERE idBrand = ?');
        $req -> execute(array($testBrand -> idBrand));
        $res = $req->fetch(\PDO::FETCH_ASSOC);

        $this -> variable($res['nameBrand'])->isEqualTo('Acme');
        $this -> variable($res['noteBrand'])->isEqualTo(10);
    }
}