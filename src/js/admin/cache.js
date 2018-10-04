function j(term) {
    return JSON.stringify(term)
}
function jp(term) {
    return JSON.stringify(term, 0, '  ')
}
function p(string) {
    return JSON.parse(string)
}

class LocalStorageLens {
    constructor(lensKey) {
        this.lensKey = lensKey
        const current = localStorage.getItem(lensKey)
        if (null === current) {
            this.memory = {}
            localStorage.setItem(lensKey, j(this.memory))
        } else {
            this.memory = p(current) || {}
        }
    }

    // return value as a promise
    // pget(key) {
    //     const self = this
    //     return new Promise(function(resolve, reject) {
    //         if (self.has(key)) {
    //             resolve(self.get(key))
    //         } else {
    //             reject(new Error("Undefined key " + key))
    //         }
    //     })
    // }

    get(key) {
        return this.memory[key]
    }

    has(key) {
        const value = this.get(key)
        return !(null === value || void 0 === value)
    }

    set(key, value) {
        if (null === value || void 0 === value) {
            delete this.memory[key]
        } else {
            this.memory[key] = value
        }
        this.write()
        return value
    }

    write() {
        localStorage.setItem(this.lensKey, j(this.memory))
    }
}

class MemoryStorage {

}


export { LocalStorageLens, MemoryStorage }
