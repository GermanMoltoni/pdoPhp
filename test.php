<?php
require_once './PDO.php';
require_once './vendor/fzaninotto/faker/src/autoload.php';
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
 
$sexo = ['M','F'];
$campos='';
$arr_col = ['ident','nombre','apellido','sexo','numdoc','tipodoc','cuil','fec_nac','idecivil','obs','baja','alta','mod'];
for ($i=0; $i < count($arr_col); $i++) { 
    if($i != count($arr_col)-1)
        $campos .= ":".$arr_col[$i].',';  
    else   
        $campos .= ":".$arr_col[$i];
}

$faker = Faker\Factory::create();
$faker->addProvider(new Faker\Provider\es_AR\Person($faker));
$faker->addProvider(new Faker\Provider\Base($faker));
$faker->addProvider(new Faker\Provider\DateTime($faker));
$faker->addProvider(new  Faker\Provider\Lorem($faker));
$arr_prueba = [];
 for ($i=0; $i < 50000; $i++) { 
    $doc =  $faker->numberBetween(5000000,99999999);
    $paciente = array(
        "ident"=>2,
        "nombre" =>$faker->firstName,
        "apellido"=> $faker->lastName,
        "sexo"=> $sexo[array_rand($sexo)],
        "numdoc"=>$doc,
        "tipodoc" => $faker->numberBetween(1,4),        
        "cuil"=> $faker->numberBetween(20,31).$doc.$faker->numberBetween(0,9),
        //$tel = $faker->e164PhoneNumber,
        "fec_nac" => $faker->date(),
        "idecivil" => $faker->numberBetween(1,4), 
        "obs"=>$faker->text(20),
        "baja"=>'1900-02-02 00:00:00',        
        "alta"=>date("Y-m-d H:i:s"),
        "mod"=>date("Y-m-d H:i:s"),
    );

    TestDB::AgregarDatos('pacientes.padron', implode(",",$arr_col),$campos,$paciente);
}

class TestDB{
    private $_manejador;
    private function __construct(){
        $manejador = new DBManejador();
    }

    public function AgregarDatos($arr_col,$campos,$paciente){
        $this->manejador->agregar('pacientes.padron', implode(",",$arr_col),$campos,$paciente);
    }
    public function MostrarDatosTabla($tabla,$arr_col){
        $manejador = new DBManejador();
        $obj = $this->manejador->consultar(implode(",",$arr_col), $tabla,true) ;
        foreach ($obj as  $value) {
            foreach($value as $val){
                echo $val.' ';
            }
              
            echo '<br>';
        }  
    }
    public function MostrarEstructuraDB(){
        $manejador = new DBManejador();
        $struct = $manejador->estructuraBD()[$this->manejador->dba];
        foreach ($struct as $esquema => $tablas) {
            echo "ESQUEMA: ".$esquema."<br>";
            foreach ($tablas as $tabla => $campos) {
                echo "TABLA: ".$tabla."<br>";
                foreach ($campos as $key => $value) {
                    echo  '          '.$value['name'].' | ';
                }
                echo "<br>";
            }
            echo "<br>";
        };
    }
}


 

?>