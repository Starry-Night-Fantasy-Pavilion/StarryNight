import Dexie, { type Table } from 'dexie';

// 定义缓存条目的接口
export interface CacheEntry {
    key: string;
    value: any;
    expires?: number; // 可选的过期时间戳
}

// 定义模型文件的接口
export interface ModelCacheEntry {
    modelId: string;
    data: ArrayBuffer;
    downloadedAt: Date;
}

// 定义数据库结构
class StarryNightDatabase extends Dexie {
    // 定义表
    public cache!: Table<CacheEntry, string>; // 'key' is the primary key
    public models!: Table<ModelCacheEntry, string>; // 'modelId' is the primary key

    constructor() {
        super('StarryNightDatabase');
        this.version(1).stores({
            // 定义表结构和索引
            // 'key' 是主键
            cache: 'key', 
            // 'modelId' 是主键
            models: 'modelId',
        });
    }

    /**
     * 获取缓存，并检查是否过期
     * @param key - 缓存键
     * @returns 缓存值或 undefined
     */
    async getCache<T>(key: string): Promise<T | undefined> {
        const entry = await this.cache.get(key);
        if (!entry) {
            return undefined;
        }
        if (entry.expires && entry.expires < Date.now()) {
            // 缓存已过期，从数据库中删除
            await this.cache.delete(key);
            return undefined;
        }
        return entry.value as T;
    }

    /**
     * 设置缓存
     * @param key - 缓存键
     * @param value - 缓存值
     * @param ttl - 生存时间 (毫秒)
     */
    async setCache(key: string, value: any, ttl?: number): Promise<void> {
        const expires = ttl ? Date.now() + ttl : undefined;
        await this.cache.put({ key, value, expires });
    }
}

// 导出数据库单例
export const db = new StarryNightDatabase();
