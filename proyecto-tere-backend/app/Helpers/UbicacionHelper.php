<?php

namespace App\Helpers;

class UbicacionHelper
{
    /**
     * Mapeo de provincias argentinas con sus vecinas
     */
    public static function getProvinciasVecinas($provincia)
    {
        $vecindades = [
            // Provincia de Buenos Aires
            'Buenos Aires' => [
                'Ciudad Autónoma de Buenos Aires',
                'Buenos Aires Province', // Para manejar variaciones
                'La Pampa',
                'Córdoba',
                'Santa Fe',
                'Entre Ríos'
            ],
            
            // Ciudad Autónoma de Buenos Aires
            'Ciudad Autónoma de Buenos Aires' => [
                'Buenos Aires',
                'Buenos Aires Province'
            ],
            
            // Córdoba
            'Córdoba' => [
                'Buenos Aires',
                'Santa Fe',
                'Santiago del Estero',
                'La Pampa',
                'San Luis'
            ],
            
            // Santa Fe
            'Santa Fe' => [
                'Buenos Aires',
                'Córdoba',
                'Santiago del Estero',
                'Chaco',
                'Corrientes',
                'Entre Ríos'
            ],
            
            // Mendoza
            'Mendoza' => [
                'San Luis',
                'La Pampa',
                'Neuquén'
            ],
            
            // Misiones
            'Misiones' => [
                'Corrientes',
                'Paraguay' // País vecino, pero lo incluimos
            ],
            
            // Catamarca
            'Catamarca' => [
                'Tucumán',
                'Santiago del Estero',
                'Salta',
                'La Rioja'
            ],
            
            // Chaco
            'Chaco' => [
                'Santa Fe',
                'Santiago del Estero',
                'Salta',
                'Formosa',
                'Corrientes'
            ],
            
            // Chubut
            'Chubut' => [
                'Santa Cruz',
                'Río Negro'
            ],
            
            // Corrientes
            'Corrientes' => [
                'Santa Fe',
                'Chaco',
                'Misiones',
                'Entre Ríos'
            ],
            
            // Entre Ríos
            'Entre Ríos' => [
                'Buenos Aires',
                'Santa Fe',
                'Corrientes'
            ],
            
            // Formosa
            'Formosa' => [
                'Chaco',
                'Salta'
            ],
            
            // Jujuy
            'Jujuy' => [
                'Salta'
            ],
            
            // La Pampa
            'La Pampa' => [
                'Buenos Aires',
                'Córdoba',
                'Mendoza',
                'Neuquén',
                'Río Negro'
            ],
            
            // La Rioja
            'La Rioja' => [
                'Catamarca',
                'Córdoba',
                'San Luis',
                'San Juan'
            ],
            
            // Neuquén
            'Neuquén' => [
                'Mendoza',
                'La Pampa',
                'Río Negro'
            ],
            
            // Río Negro
            'Río Negro' => [
                'La Pampa',
                'Neuquén',
                'Chubut'
            ],
            
            // Salta
            'Salta' => [
                'Jujuy',
                'Formosa',
                'Chaco',
                'Santiago del Estero',
                'Tucumán',
                'Catamarca'
            ],
            
            // San Juan
            'San Juan' => [
                'La Rioja',
                'San Luis',
                'Mendoza'
            ],
            
            // San Luis
            'San Luis' => [
                'Córdoba',
                'La Rioja',
                'San Juan',
                'Mendoza',
                'La Pampa'
            ],
            
            // Santa Cruz
            'Santa Cruz' => [
                'Chubut'
            ],
            
            // Santiago del Estero
            'Santiago del Estero' => [
                'Salta',
                'Chaco',
                'Santa Fe',
                'Córdoba',
                'Tucumán',
                'Catamarca'
            ],
            
            // Tierra del Fuego
            'Tierra del Fuego' => [
                // Sin provincias vecinas
            ],
            
            // Tucumán
            'Tucumán' => [
                'Salta',
                'Santiago del Estero',
                'Catamarca'
            ]
        ];
        
        // Normalizar nombre de provincia
        $provincia = self::normalizarNombreProvincia($provincia);
        
        return $vecindades[$provincia] ?? [];
    }
    
    /**
     * Normalizar nombre de provincia
     */
    public static function normalizarNombreProvincia($provincia)
    {
        $mapeo = [
            'Buenos Aires Province' => 'Buenos Aires',
            'Ciudad Autónoma de Buenos Aires' => 'Ciudad Autónoma de Buenos Aires',
            'CABA' => 'Ciudad Autónoma de Buenos Aires',
            'Capital Federal' => 'Ciudad Autónoma de Buenos Aires',
            'Cordoba' => 'Córdoba',
            'Santa Fe' => 'Santa Fe',
            'Mendoza' => 'Mendoza',
            'Misiones' => 'Misiones',
            'Catamarca' => 'Catamarca',
            'Chaco' => 'Chaco',
            'Chubut' => 'Chubut',
            'Corrientes' => 'Corrientes',
            'Entre Ríos' => 'Entre Ríos',
            'Formosa' => 'Formosa',
            'Jujuy' => 'Jujuy',
            'La Pampa' => 'La Pampa',
            'La Rioja' => 'La Rioja',
            'Neuquén' => 'Neuquén',
            'Río Negro' => 'Río Negro',
            'Salta' => 'Salta',
            'San Juan' => 'San Juan',
            'San Luis' => 'San Luis',
            'Santa Cruz' => 'Santa Cruz',
            'Santiago del Estero' => 'Santiago del Estero',
            'Tierra del Fuego' => 'Tierra del Fuego',
            'Tucumán' => 'Tucumán'
        ];
        
        return $mapeo[$provincia] ?? $provincia;
    }
    
    /**
     * Obtener todas las provincias argentinas
     */
    public static function getProvinciasArgentinas()
    {
        return [
            'Buenos Aires',
            'Ciudad Autónoma de Buenos Aires',
            'Córdoba',
            'Santa Fe',
            'Mendoza',
            'Misiones',
            'Catamarca',
            'Chaco',
            'Chubut',
            'Corrientes',
            'Entre Ríos',
            'Formosa',
            'Jujuy',
            'La Pampa',
            'La Rioja',
            'Neuquén',
            'Río Negro',
            'Salta',
            'San Juan',
            'San Luis',
            'Santa Cruz',
            'Santiago del Estero',
            'Tierra del Fuego',
            'Tucumán'
        ];
    }
}