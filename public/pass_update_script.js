
var y = db.passes.updateMany({
        pass_id:{
            $in: [20,23,24,41]
        }
    },
    {
        $set: {
            'premium_min_booking_price_restriction': 350,
            "max_booking_count" : 2,
            "premium_sessions": 1,
            "premium_booking_price" : [ 
                {
                    "city_ids" : [ 
                        3, 
                        4
                    ],
                    "min" : 400,
                    "max" : 750
                }
            ]
        }
    }
)

// var x = db.passes.updateMany(
//     {
//         pass_type:"red", 
//         status: "1",
//         vendor_restriction: null
//     }, 
//     {
//         $set: {
//             "vendor_restriction" : 
//             [ 
//                 {
//                     "ids" : [ 
//                         13660
//                     ],
//                     "duration" : 30,
//                     "duration_type" : "days",
//                     "start_date" : ISODate("2019-11-24T00:02:03.131Z"),
//                     "type" : "finders",
//                     "count" : 5,
//                     "count_type" : "each"
//                 }, 
//                 {
//                     "ids" : [ 
//                         966
//                     ],
//                     "duration" : 30,
//                     "duration_type" : "days",
//                     "start_date" : ISODate("2019-11-24T00:02:03.131Z"),
//                     "type" : "finders",
//                     "count" : 8,
//                     "count_type" : "each"
//                 }, 
//                 {
//                     "ids" : [ 
//                         11230
//                     ],
//                     "duration" : 30,
//                     "duration_type" : "days",
//                     "start_date" : ISODate("2019-11-24T00:02:03.131Z"),
//                     "type" : "finders",
//                     "count" : 10,
//                     "count_type" : "each"
//                 }, 
//                 {
//                     "ids" : [ 
//                         1935, 
//                         9304, 
//                         9423, 
//                         9481, 
//                         9600, 
//                         9932, 
//                         9954, 
//                         10674, 
//                         10970, 
//                         11021, 
//                         11223, 
//                         12208, 
//                         12209, 
//                         13094, 
//                         13898, 
//                         13968, 
//                         14102, 
//                         14107, 
//                         14622, 
//                         14626, 
//                         14627, 
//                         15431, 
//                         15775, 
//                         15980, 
//                         16062, 
//                         16251, 
//                         16449, 
//                         16450, 
//                         16562, 
//                         16636, 
//                         16644, 
//                         17470
//                     ],
//                     "duration" : 30,
//                     "duration_type" : "days",
//                     "type" : "finders",
//                     "count" : 12,
//                     "count_type" : "each"
//                 }
//             ]
//         }
//     }
// )
