<?php
 

class DBManejador extends PDO
{
  
 public $srv = SRV;  
 public $usr = USR;  
 public $pas = PAS;  
 public $dba = BDA;  
 public $prt = PRT;  
 public $numrows= null;
 public $conexion;
 public $manejador;
 
public function __construct() { $this->conectar(); }

 
private  function conectar() {
    $conex = null;
    try{
        if( is_array( PDO::getAvailableDrivers() ) ){
            if( in_array( "pgsql",PDO::getAvailableDrivers() ) ){
                $conStr = sprintf("pgsql:host=%s;port=%d;dbname=%s;user=%s;password=%s", 
                $this->srv, 
                $this->prt, 
                $this->dba, 
                $this->usr, 
                $this->pas);
                $conex = new PDO($conStr);
                $conex->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);                
                $this->setManejador('pgsql');
            }
            else
                throw new PDOException ("No se puede trabajar sin establecer una conexión adecuada con la base de datos de mysql");
        }
    }
    catch(PDOException $e){
        print_r($e->getMessage());
        error_log( $e->getMessage() ); 
    }
    $this->setConexion( $conex );
 }
 

  
public final function consultar($columnas, $tabla, $getObjects=false)
{
    $rt = null;
    try{
        $query = $this->conexion->prepare( " SELECT " . $columnas . " FROM " . $tabla );
        $query->execute();
        $this->setNumRows( $query->rowCount() );
       // $this->cerrarConexion();
        if( $getObjects )
            $rt = $query->fetchAll(PDO::FETCH_ASSOC);
        else
            $rt = $query->fetchAll(PDO::FETCH_OBJ);
    }
    catch(PDOException $e){
        var_dump($e->getMessage());
        error_log( $e->getMessage() ); 
    }
    return $rt;
}

public final function consultarCondicion($columnas, $tabla, $condicion, $valores)
{
    $rt = null;
    try{
        $query = $this->conexion->prepare( " SELECT " . $columnas . " FROM " . $tabla . " WHERE " . $condicion );
        foreach ($valores as $key => $value){
            if( !empty( $value ) )
                $query->bindParam( $key, $value, $this->getPDOConstantType( $value ) );
        }
        $query->execute();
        $this->setNumRows( $query->rowCount() );
      //  $this->cerrarConexion();
        $rt = $query->fetchAll(PDO::FETCH_ASSOC);
    }
    catch(PDOException $e) {
        var_dump($e->getMessage());
        error_log( $e->getMessage() ); 
    }
    return $rt;
}

public final function agregar($tabla, $columnas, $campos, $valores){ 
    $rt = null;
    try{
        $query = $this->conexion->prepare( "INSERT INTO ".$tabla." (".$columnas.") VALUES (".$campos.");" );

        
        foreach ($valores as $key => $value){
            if( !empty( $value ) )
                $query->bindParam( $key,strval($value));
        }
        $rt = $query->execute();
        
        $this->setNumRows( $query->rowCount() );
        //$this->cerrarConexion();
    }
    catch(PDOException $e){
        var_dump($e->getMessage());
        error_log( $e->getMessage() ); 
    }
    return $rt; 
}
 

public final function actualizar($tabla, $campos, $valores, $condicion){
    $rt = null;
    try{
        $query = $this->conexion->prepare( "UPDATE ".$tabla." SET ".$campos." WHERE ".$condicion );
        foreach ($valores as $key => $value){
            if( !empty( $value ) )
                $query->bindParam( $key, $value, $this->getPDOConstantType( $value ) );
        }
        $rt = $query->execute();
        $this->setNumRows( $query->rowCount() );
       // $this->cerrarConexion();
    }
    catch(PDOException $e){
        var_dump($e->getMessage());
        error_log( $e->getMessage() ); 
    }
    return $rt; 
}

public final function eliminar($tabla, $condicion, $valores){
    $rt = null;
    try{
        $query = $this->conexion->prepare( "DELETE FROM ".$tabla." WHERE ".$condicion );
        foreach ($valores as $key => $value) {
            if( !empty( $value ) )
                $query->bindParam( $key, $value, $this->getPDOConstantType( $value ) );
        } 
        $rt = $query->execute();    
        $this->setNumRows( $query->rowCount() );
      //  $this->cerrarConexion();
    }
    catch(PDOException $e){
        var_dump($e->getMessage());
        error_log( $e->getMessage() ); 
    }
    return $rt; 
}

public  function estructuraBD(){
    $rt = array( $this->dba => array() ); 
    if( $this->getManejador() == 'pgsql' ){
        $query = $this->conexion->prepare("SELECT table_schema FROM information_schema.tables WHERE table_schema NOT IN ( 'pg_catalog', 'information_schema' ) AND table_catalog = '" . $this->dba . "' GROUP BY table_schema ORDER BY table_schema");
        $query->execute();
        $esquemas = $query->fetchAll(PDO::FETCH_ASSOC);
        foreach ($esquemas as $indice_esquema => $valor_esquema) {
            if( !empty( $valor_esquema ) ){
                $rt[ $this->dba ][ $valor_esquema['table_schema'] ] = array();
                $query = $this->conexion->prepare("SELECT table_name FROM information_schema.tables WHERE table_schema NOT IN ( 'pg_catalog', 'information_schema' ) AND table_catalog = '" . $this->dba . "' AND table_schema = '" . $valor_esquema['table_schema'] . "' ORDER BY table_schema");
                $query->execute();
                $tablas = $query->fetchAll(PDO::FETCH_ASSOC);
                foreach ($tablas as $indice_tabla => $valor_tabla) {
                    if( !empty( $valor_tabla ) ){
                        $rt[ $this->dba ][ $valor_esquema['table_schema'] ][ $valor_tabla['table_name'] ] = array();
                        $query = $this->conexion->prepare(" SELECT * FROM " . $valor_esquema['table_schema'] . "." . $valor_tabla['table_name'] . " LIMIT 1" );
                        $query->execute();  
                        $columnas = $query->fetchAll(PDO::FETCH_ASSOC);
                        $constrains = $this->conexion->prepare(" SELECT ccu.column_name AS columna, ccu.constraint_name AS alias, tc.constraint_type AS restrinccion FROM information_schema.key_column_usage AS ccu, information_schema.table_constraints AS tc WHERE ccu.constraint_name = tc.constraint_name AND ccu.table_name = '" . $valor_tabla['table_name'] . "' AND tc.table_name = '" . $valor_tabla['table_name'] . "'" );
                        $constrains->execute();
                        $restrincciones = $constrains->fetchAll(PDO::FETCH_ASSOC);
                        foreach ($columnas as $indice_columnas => $valor_columnas){
                            if( !empty( $valor_columnas ) ){
                                $cantidad_columnas = $query->columnCount();
                                for($i = 0; $i < $cantidad_columnas; $i++){
                                    $detalles = $query->getColumnMeta( $i );
                                    foreach ($restrincciones as $key => $value) {
                                        if( !empty( $value ) ){
                                            if( $detalles['name'] == $value['columna'] ){
                                                $detalles['alias'] = $value['alias'];
                                                $detalles['restrinccion'] = $value['restrinccion'];
                                            }
                                        }
                                    }
                                    $rt[ $this->dba ][ $valor_esquema['table_schema'] ][ $valor_tabla['table_name'] ][$i] = $detalles;
                                }
                            }
                        }
                    }
                }
            }
        }
    }
    return $rt;
}
 

  
public  function cerrarConexion(){
    $this->setConexion(null);
}
 
  
 public final function getConexion(){
    return $this->conexion; 
}
 
 
  
 private final function setConexion($conexion){
    $this->conexion = $conexion;
}
 
 
  
public final function getManejador(){
    return $this->manejador; 
}
 
 
private final function setManejador($manejador){
    $this->manejador = $manejador;
}
 
 
private final function setNumRows( $rows ){
    $this->numrows = $rows;
}
 
 
 
public final function getNumRows(){
    return $this->numrows;
}
 
public final function getDba(){
    return $this->dba;
}
 
 
private function getPDOConstantType($var){
    if( is_int( $var ) )
        return PDO::PARAM_INT;
    if( is_bool( $var ) )
        return PDO::PARAM_BOOL;
    if( is_null( $var ) )
        return PDO::PARAM_NULL;
    return PDO::PARAM_STR;
    }
}



?>

