<html>
    <body>
        <h3>获取到你的信息啦，还想看电影？呵呵</h3>
        <img src="{{$data['headimgurl']}}"  alt="{{$data['nickname']}}" />
        <ol>
            <li>openid:{{$data['openid']}}</li>
            <li>昵称:{{$data['nickname']}}</li>
            <li>性别:{{$data['sex']}}</li>
            <li>国家:{{$data['country']}}</li>
            <li>省份:{{$data['province']}}</li>
            <li>城市:{{$data['city']}}</li>
        </ol>
    </body>
</html>