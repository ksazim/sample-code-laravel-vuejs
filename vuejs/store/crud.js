import { defineStore } from 'pinia';
import axios from 'axios'
import { useCommonStore } from '@/store/common'

export const useCrudStore = defineStore({
    id: 'crud',
    state: () => ({
        getAllData: [],
        getAllDataPagination: [],
        getDataById: {},
        getPhoto: {},
        getSingleData: {},

        nextPage: null, 
        loading: false,
        allLoaded: false
    }),
    actions: {
        async getAllInfinite(api, queryParams = {}) {
            if (this.loading || this.allLoaded) return;
            console.log(queryParams);
            this.loading = true;
        
            const token = localStorage.getItem('token');
        
            try {
                let url = new URL(this.nextPage ?? process.env.VUE_APP_BASE_API_URL + api);
                
                Object.keys(queryParams).forEach(key => {
                    url.searchParams.append(key, queryParams[key]);
                });
        
                axios.defaults.headers.common['Authorization'] = `Bearer ${token}`;
        
                const response = await axios.get(url.toString()); 
        
                this.getAllData.push(...response.data.list);
                this.nextPage = response.data.next_page_url;
                if (!response.data.next_page_url) this.allLoaded = true;
            } catch (e) {
                return e;
            } finally {
                this.loading = false;
            }
        },
        

        async getAll(api) {
            const token = localStorage.getItem('token');
            try {
                // const url = this.nextPage ?? process.env.VUE_APP_BASE_API_URL + api;

                axios.defaults.headers.common['Authorization'] = `Bearer ${token}`
                const response = await axios.get(process.env.VUE_APP_BASE_API_URL + api)
                this.getAllData = response.data.list
            } catch(e) {
                return e
            } finally {
                // this.loading = false;  // Stop loading once data is fetched
            }
        },
        

        async getSingle(api) {
            const commonStore = useCommonStore()
            const token = localStorage.getItem('token');
            try {
                axios.defaults.headers.common['Authorization'] = `Bearer ${token}`
                const response = await axios.get(process.env.VUE_APP_BASE_API_URL+api)
                this.getSingleData = response.data.single
            } catch(e) {
                return e
            }
            commonStore.loading = false
        },

        async getById(api, id) {
            const response = await axios.get(process.env.VUE_APP_BASE_API_URL+api+id)
            this.getDataById = response.data
            if(this.getDataById.media) {
                if(this.getDataById.media.length) {
                    this.getPhoto = this.getDataById.media[0].base_path+'/'+this.getDataById.media[0].file_name
                } else {
                    this.getPhoto = null
                }
            } else if(this.getDataById.product) {
                if(this.getDataById.product.media.length) {
                    this.getPhoto = this.getDataById.product.media[0].base_path+'/'+this.getDataById.product.media[0].file_name
                } else {
                    this.getPhoto = null
                }
            } else {
                this.getPhoto = null
            }
        },

        async newData(api, data) {
            try {
                const token = localStorage.getItem('token');
                axios.defaults.headers.common['Authorization'] = `Bearer ${token}`
                return await axios.post(process.env.VUE_APP_BASE_API_URL+api, data)
            } catch(e) {
                return await e
            }
        },

        async updateData(api, data, config) {
            try {
                if(data.id) {
                    const token = localStorage.getItem('token');
                    axios.defaults.headers.common['Authorization'] = `Bearer ${token}`
                    return await axios.post(process.env.VUE_APP_BASE_API_URL+api+'/'+data.id, data, config)
                } else {
                    return await axios.post(process.env.VUE_APP_BASE_API_URL+api+'/'+data.get('id'), data, config)
                }
            } catch(e) {
                return await e
            }
        },

        async destroy(api, id) {
           try {
            const token = localStorage.getItem('token');
            axios.defaults.headers.common['Authorization'] = `Bearer ${token}`
            return await axios.get(process.env.VUE_APP_BASE_API_URL+api+id)
           } catch(e) {
            return e
           }
        }
    }
});

