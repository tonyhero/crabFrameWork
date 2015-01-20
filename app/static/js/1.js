//var a = {
//    "result": true,
//    "errno": 10001,
//    "errmsg": "no object can be find",
//    "details": {
//        form_types: [
//            {
//                id: "1",
//                name: "一级分类一",
//                subtypes: [
//                    {
//                        id: "11",
//                        name: "二级分类一",
//                        form_files: [
//                            {
//                                label: "姓名",
//                                name: "name",
//                                required: true
//                            },
//                            {
//                                label: "邮箱",
//                                name: "email",
//                                required: true,
//                                type: "email"
//                            }
//                        ]}
//                ]}
//        ],
//        gameserver : []
//    }
//
//}


var fields = [
    {
        name: "game",
        type: "select",
        label: "游戏",
        sub: {
            name: "channel",
            mapping: {

            }
        },
        data: {
            type: "url",
            url: "http://www.aaaaa.com/aaaaa"
        }
    },
    {
        name: "channel",
        label: "渠道",
        sub: "server"
    },
    {
        name: "server",
        label: "服务器"
    }
];


var obj = {
    "games1": {
        name: "game1",
        sub: {
            "channel1": {
                name: "channel1",
                sub: {
                    "server1": {
                        name: "server1"
                    }

                }
            }
        }

    }

}


var array = [
    {"name": "gamename", "label": "游戏名称", "required": true, "type": "dropdown", "data_source": "game_servers", "sub": "channel"},
    {"name": "channel", "label": "版本渠道", "required": true, "type": "dropdown", "sub": "servername"},
    {"name": "servername", "label": "区服名称", "required": true, "type": "dropdown"},
    {"name": "gamerole", "label": "角色名称", "required": true},
    {"name": "last_pay_screenshot", "label": "最后充值截图", "type": "file"},
    {"name": "contact_way", "label": "联系方式", "required": true},
    {"name": "impress_account", "label": "可能的游戏账号", "type": "email"},
    {"name": "mobile", "type": "mobile", "label": "手机号码"},
    {"name": "id_no", "type": "id_no", "label": "身份证号码"},
    {"name": "mac", "type": "text", "label": "MAC地址"}
]
//
//
//    {"name": "truename", "label": "姓名", "required": true},
//    {"name": "birthday", "label": "生日", "required": true},
//    {"name": "address", "label": "住址", "required": true},
//    {"name": "mac", "label": "mac地址", "required": true},
//    {"name": "regist_time", "label": "注册时间", "required": true},
//
//    {"name": "vip_level", "label": "VIP等级", "required": true},
//    {"name": "sumpay", "label": "充值总额", "required": true},
//    {"name": "last_login_time", "label": "最后登录时间", "required": true},
//    {"name": "pay_orderid", "label": "充值订单号", "required": true},
//    {"name": "impress_account", "label": "印象账号", "required": true,"type":"email"},
//    {"name": "contact_email", "label": "联系邮箱", "required": true,"type":"email"},
//    {"name": "player_createtime", "label": "创建时间", "required": true},
//    {"name": "upload_file", "label": "上传附件", "required": true, "type": "file"},
//    {"name": "description", "label": "问题描述", "require": true}
//]
//
//
//
//
//
//    [
//    {"name": "mac", "label": "mac地址", "required": true},
//    {"name": "regist_time", "label": "注册时间", "required": true},
//    {"name": "gamename", "label": "游戏名称", "required": true, "type": "dropdown", "data_source": "game_servers", "sub": "channel"},
//    {"name": "channel", "label": "版本渠道", "required": true, "type": "dropdown", "sub": "servername"},
//    {"name": "servername", "label": "区服名称", "required": true, "type": "dropdown"},
//    {"name": "gamerole", "label": "角色名称", "required": true},
//    {"name": "pay_orderid", "label": "充值订单号", "required": true},
//    {"name": "device_num", "label": "曾使用设备数量", "required": true},
//    {"name": "first_pay_screenshot", "label": "首次充值截图", "required": true, "type": "file"},
//    {"name": "last_pay_screenshot", "label": "最后充值截图", "required": true, "type": "file"},
//    {"name": "contact_way", "label": "联系方式", "required": true},
//    {"name": "mobile_change_to", "type": "mobile", "label": "修改手机为", "require": true},
//    {"name": "description", "label": "问题描述", "require": true},
//    {"name": "player_account", "label": "玩家登陆的账号", "required": true},
//    {"name": "gamer_uid", "type": "int", "label": "玩家纵乐的uid", "required": true}
//    ][