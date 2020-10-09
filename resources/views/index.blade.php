<!DOCTYPE html>
<html lang="zh-CN">

<head>
    <meta charset="utf-8" />
    <title>短链接生成</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0,maximum-scale=1.0, user-scalable=no" />
    <meta name="renderer" content="webkit" />
    <meta name="ROBOTS" content="noarchive" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1" />
    <meta content="telephone=no; email=no" name="format-detection" />
    <link rel="stylesheet" href="https://lib.baomitu.com/element-ui/2.12.0/theme-chalk/index.css" />
    <script src="https://lib.baomitu.com/vue/2.6.10/vue.min.js"></script>
    <script src="https://lib.baomitu.com/axios/0.19.2/axios.min.js"></script>
    <script src="https://lib.baomitu.com/element-ui/2.12.0/index.js"></script>
    <style>
        body {
            margin: 0;
            padding-top: 40px;
            padding-bottom: 40px;
            box-sizing: border-box;
        }

        #app {
            width: 862px;
            margin: 0 auto;
            padding: 40px;
            box-sizing: border-box;
            border-radius: 5px;
            border: 1px solid #f4f4f5;
            background-color: #fff;
        }

        .title {
            margin-bottom: 20px;
            text-align: center;
        }
    </style>
</head>

<body>
    <div id="app">
        <div class="title">短链接生成</div>

        <el-input placeholder="请输入 Url" v-model="url">
            <el-button slot="append" :loading="loading" @click="handleGenerate">生成</el-button>
        </el-input>

        <el-alert v-if="url_result" :title="url_result" type="success" :closable="false" center style="margin-top: 20px;"></el-alert>
    </div>

    <script>
        const vm = new Vue({
            el: '#app',
            data: {
                loading: false,
                url: 'https://www.hongfs.cn/',
                url_result: '',
            },
            mounted () {
            },
            methods: {
                handleGenerate () {
                    this.loading = true;

                    axios.post(`{{ action('IndexController@store') }}`, {
                        url: this.url,
                    }).then(response => {
                        const data = response.data;
                        if(data.code === 1) {
                            this.url_result = data.data.url;
                        } else {
                            this.$message.closeAll();
                            this.$message.error(data.message);
                        }
                        this.loading = false;
                    }).catch(err => {
                        this.loading = false;
                    });
                },
            },
        });
    </script>
</body>

</html>
