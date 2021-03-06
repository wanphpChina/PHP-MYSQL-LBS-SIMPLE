<?php
class Main {
 /**
     * 根据经纬度查询用户列表
     * @param double $lati  经度
     * @param double $longi 维度
     * @param double $range 范围,单位公里,默认是10公里以内
     * @return list[]       学校节点列表
     */
    public function getSchoolNodeListByGps($lati, $longi, $range = 5) {
        $sql = "SELECT * FROM user WHERE
                MBRContains (
                    LineString (
                            Point( ? + ? / ( 111.1 / COS(RADIANS(?))), ? + ? / 111.1),
                            Point(? - ? / ( 111.1 / COS(RADIANS(?))), ? - ? / 111.1)
                            ),
                     location_point
                            )
                ORDER BY (power(ABS(?-X(location_point)),2) + power(ABS(?-Y(location_point)),2))";
        //$sql = "SELECT *, ( 6371.004 * acos( cos( radians(?) ) * cos( radians( latitude ) ) * cos( radians ( longitude ) - radians(?) ) + sin( radians(?) ) * sin( radians( latitude ) ) ) ) AS distance FROM user HAVING distance < ? ORDER BY distance LIMIT 100;";
        //above is easy but very slow method 
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
                $lati,
                $range,
                $longi,
                $longi,
                $range,
                $lati,
                $range,
                $longi,
                $longi,
                $range,
                $lati,
                $longi,
        ]);
//         $stmt->execute([
//                 $lati,
//                 $longi,
//                 $lati,
//                 $range,
//         ]);
        $list = $stmt->fetchAll();
        foreach ($list as $key => $v) {
            unset($list[$key]['location_point']);
            $list[$key]['distance'] = $this->distanceBetween($lati, $longi, $v['latitude'], $v['longitude']);
        }
        return $list;
    }  
  
/**
 * 计算两个坐标之间的距离(米)
 * @param float $fP1Lat 起点(纬度)
 * @param float $fP1Lon 起点(经度)
 * @param float $fP2Lat 终点(纬度)
 * @param float $fP2Lon 终点(经度)
 * @return int
 */
function distanceBetween($fP1Lat, $fP1Lon, $fP2Lat, $fP2Lon){
    $fEARTH_RADIUS = 6378137;
    //角度换算成弧度
    $fRadLon1 = deg2rad($fP1Lon);
    $fRadLon2 = deg2rad($fP2Lon);
    $fRadLat1 = deg2rad($fP1Lat);
    $fRadLat2 = deg2rad($fP2Lat);
    //计算经纬度的差值
    $fD1 = abs($fRadLat1 - $fRadLat2);
    $fD2 = abs($fRadLon1 - $fRadLon2);
    //距离计算
    $fP = pow(sin($fD1/2), 2) +
          cos($fRadLat1) * cos($fRadLat2) * pow(sin($fD2/2), 2);
    return intval($fEARTH_RADIUS * 2 * asin(sqrt($fP)) + 0.5);
}
}
