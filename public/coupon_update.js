
// var coupons =  ["fitternityuw7rybqqevcred","fitternitywt27ycmgy7cred","fitternitymglhnxgfvkcred","fitternity2phahgl770cred","fitternity23u4bsgx9fcred","fitternity5vwrdjpuykcred","fitternitye1awbrcxzjcred","fitternityy2ns7ykowucred","fitternity31qzs4ihyhcred","fitternity2cid9u5ms4cred","fitternity6q6tids32ocred","fitternityx5pn4irn0qcred","fitternity432ngcilyacred","fitternityq41wyjaqnccred","fitternityeki48nn0ancred","fitternityqfqt375ls4cred","fitternitywi8xf6hqx4cred","fitternity3bolgx83xjcred","fitternityrnyhh2pmnhcred","fitternityqk0zhf6z63cred", "fitternity39esuvp3yncred","fitternitympbl7sowfbcred","fitternitye5we5dubd0cred","fitternityegat95oz8ncred","fitternitymvdyhkq5h5cred"];

// var x = db.coupons.update({"code": {"$in": coupons}, total_used: 0}, {$set: {
//     "and_conditions" : [ 
//     {
//         "key" : "pass.pass_type",
//         "operator" : "in",
//         "values" : [ 
//             "red"
//         ]
//     }, 
//     {
//         "key" : "pass.duration",
//         "operator" : "in",
//         "values" : [ 
//             NumberLong(90)
//         ]
//     }, 
//     {
//         "key" : "customer_source",
//         "operator" : "in",
//         "values" : [ 
//             "cred"
//         ]
//     }, 
//     {
//         "key" : "corporate_source",
//         "operator" : "in",
//         "values" : [ 
//             "generic"
//         ]
//     }
// ]}})

// var coupons_180 = ["fitternityhvbdagr5r4cred","fitternity66lgzvmouucred","fitternitybhqpf896ercred","fitternitybwnmax313vcred","fitternity691sq1ncqicred","fitternity72zyrf71llcred","fitternityswhfjsdntgcred","fitternityi0qksgmftccred","fitternityx0fxz6c67xcred","fitternitys0uafe2t1vcred"]

// var y = db.coupons.update({"code": {"$in": coupons_180}, total_used: 0}, {$set: {
//     "and_conditions" : [ 
//         {
//             "key" : "pass.pass_type",
//             "operator" : "in",
//             "values" : [ 
//                 "red"
//             ]
//         }, 
//         {
//             "key" : "pass.duration",
//             "operator" : "in",
//             "values" : [ 
//                 NumberLong(180)
//             ]
//         },
//         {
//             "key" : "customer_source",
//             "operator" : "in",
//             "values" : [ 
//                 "cred"
//             ]
//         }, 
//         {
//             "key" : "corporate_source",
//             "operator" : "in",
//             "values" : [ 
//                 "generic"
//             ]
//         }
// ]}})

// var coupons_360 = ["fitternityajv04ohq4acred","fitternity31bizapbhwcred","fitternity99w4jcil5kcred","fitternityhg4cg81xz5cred","fitternity827jl6uaibcred"];

// var y = db.coupons.update({"code": {"$in": coupons_360}, total_used: 0}, {$set: {
//     "and_conditions" : [ 
//         {
//             "key" : "pass.pass_type",
//             "operator" : "in",
//             "values" : [ 
//                 "red"
//             ]
//         }, 
//         {
//             "key" : "pass.duration",
//             "operator" : "in",
//             "values" : [ 
//                 NumberLong(360)
//             ]
//         },
//         {
//             "key" : "customer_source",
//             "operator" : "in",
//             "values" : [ 
//                 "cred"
//             ]
//         }, 
//         {
//             "key" : "corporate_source",
//             "operator" : "in",
//             "values" : [ 
//                 "generic"
//             ]
//         }
// ]}})