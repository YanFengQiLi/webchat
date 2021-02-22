import axios from 'axios';
//  设置全局 axios 的 baseURL 属性
const baseURL = '/api';
//  创建 axios 实例对象
const instance = axios.create();
//  设置全局 axios 的请求超时时间为 30s
instance.defaults.timeout = 30000;
//  设置全局 axios 的请求方式, X-Requested-With == null 则为同步请求, X-Requested-With == 'XMLHttpRequest', 则为 ajax 异步请求
instance.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';

//  利用 document 对象获取匹配头部中 meta标签 name='csrf-token'
let token = document.head.querySelector('meta[name="csrf-token"]');
if (token) {
    //  设置全局 axios 请求头中 X-CSRF-TOKEN 的值, 注意匹配到的 document.querySelector 是一个对象, 利用 token.content 能获取其值
    instance.defaults.headers.common['X-CSRF-TOKEN'] = token.content;
} else {
    console.error('CSRF token not found: https://laravel.com/docs/csrf#csrf-x-csrf-token');
}

// 设置 axios 请求拦截器, 在请求被 then 或 catch 处理前拦截它们, 做统一处理
instance.interceptors.request.use(async config => {
    if (config.url && config.url.charAt(0) === '/') {
        config.url = `${baseURL}${config.url}`;
    }
    return config;
}, error => Promise.reject(error));

// 设置 axios 响应拦截器, 对返回的内容做统一处理
instance.interceptors.response.use(response => {
    if (response.status === 200) {
        return response;
    }
    return Promise.reject(response);
}, error => {
    if (error) {
        console.log(JSON.stringify(error));
    } else {
        console.log('出了点问题，暂时加载不出来，请稍后再来吧');
    }
    return Promise.reject(error);
});

export default instance;
